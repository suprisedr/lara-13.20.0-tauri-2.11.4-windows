<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ConsolidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupConsolidationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompany(User $user, string $name, array $attrs = []): Company
    {
        return Company::create(array_merge([
            'user_id' => $user->id,
            'registered_name' => $name,
            'company_type' => 'pty_ltd',
            'financial_year_end_month' => 12,
        ], $attrs));
    }

    private function account(Company $c, string $code, string $name, string $type, float $opening = 0): ChartOfAccount
    {
        return ChartOfAccount::create([
            'company_id' => $c->id,
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => $type,
            'is_active' => true,
            'opening_balance' => $opening,
        ]);
    }

    public function test_consolidated_balance_sheet_applies_goodwill_and_nci(): void
    {
        $user = User::factory()->create();

        $parent = $this->makeCompany($user, 'Parent Holdings', [
            'is_consolidation_parent' => true,
        ]);

        $sub = $this->makeCompany($user, 'Sub Co', [
            'parent_company_id' => $parent->id,
            'group_ownership_percentage' => 80,
            'investment_cost' => 300,
            'equity_at_acquisition' => 350,
        ]);

        // Parent: assets 1000 (incl. investment 300), liabilities 200, equity 800.
        $this->account($parent, '1000', 'Bank', 'assets', 700);
        $this->account($parent, '1600', 'Investment in Sub', 'assets', 300);
        $this->account($parent, '2600', 'Long-term loan', 'liabilities', 200);
        $this->account($parent, '3000', 'Share capital', 'equity', 800);

        // Sub: assets 500, liabilities 100, equity 400.
        $this->account($sub, '1000', 'Bank', 'assets', 500);
        $this->account($sub, '2000', 'Payables', 'liabilities', 100);
        $this->account($sub, '3000', 'Share capital', 'equity', 400);

        $result = (new ConsolidationService())->balanceSheet($parent, '2026-12-31');

        // Goodwill = 300 − 80% × 350 = 20
        $this->assertEqualsWithDelta(20.0, $result['goodwill'], 0.01);
        // NCI = 20% × 400 = 80
        $this->assertEqualsWithDelta(80.0, $result['nci'], 0.01);
        // Assets = (1000 + 500) − 300 investment + 20 goodwill = 1220
        $this->assertEqualsWithDelta(1220.0, $result['totalAssets'], 0.01);
        // Liabilities = 200 + 100 = 300
        $this->assertEqualsWithDelta(300.0, $result['totalLiabilities'], 0.01);
        // Equity = 1220 − 300 = 920
        $this->assertEqualsWithDelta(920.0, $result['totalEquity'], 0.01);
        // Owners' equity = 920 − 80 = 840
        $this->assertEqualsWithDelta(840.0, $result['ownersEquity'], 0.01);
        // Balance sheet balances.
        $this->assertEqualsWithDelta(
            $result['totalAssets'],
            $result['totalLiabilities'] + $result['totalEquity'],
            0.01
        );
    }

    public function test_consolidated_profit_attributes_to_nci(): void
    {
        $user = User::factory()->create();

        $parent = $this->makeCompany($user, 'Parent Holdings', [
            'is_consolidation_parent' => true,
        ]);
        $sub = $this->makeCompany($user, 'Sub Co', [
            'parent_company_id' => $parent->id,
            'group_ownership_percentage' => 80,
        ]);

        // Parent: revenue 2000, expense 1500 → profit 500.
        $this->postProfit($parent, $user, 2000, 1500);
        // Sub: revenue 1000, expense 600 → profit 400.
        $this->postProfit($sub, $user, 1000, 600);

        $result = (new ConsolidationService())->incomeStatement($parent, '2026-01-01', '2026-12-31');

        $this->assertEqualsWithDelta(3000.0, $result['revenue'], 0.01);
        $this->assertEqualsWithDelta(2100.0, $result['expenses'], 0.01);
        $this->assertEqualsWithDelta(900.0, $result['profit'], 0.01);
        // NCI profit = 20% × 400 = 80
        $this->assertEqualsWithDelta(80.0, $result['nciProfit'], 0.01);
        // Owners' profit = 900 − 80 = 820
        $this->assertEqualsWithDelta(820.0, $result['ownersProfit'], 0.01);
    }

    public function test_group_pages_render_and_subsidiary_flow_works(): void
    {
        $user = User::factory()->create();
        $parent = $this->makeCompany($user, 'Parent Holdings');
        $sub = $this->makeCompany($user, 'Sub Co');

        // Structure page renders before any group is configured.
        $this->actingAs($user)
            ->get(route('companies.group.structure', $parent))
            ->assertOk()
            ->assertSee('Add a subsidiary');

        // Add the subsidiary — this also flags the parent as a consolidation parent.
        $this->actingAs($user)
            ->post(route('companies.group.subsidiaries.add', $parent), [
                'subsidiary_id' => $sub->id,
                'group_ownership_percentage' => 75,
                'investment_cost' => 100,
                'equity_at_acquisition' => 120,
            ])
            ->assertRedirect(route('companies.group.structure', $parent));

        $this->assertDatabaseHas('companies', [
            'id' => $sub->id,
            'parent_company_id' => $parent->id,
            'group_ownership_percentage' => 75,
        ]);
        $this->assertTrue($parent->fresh()->is_consolidation_parent);

        // Give each entity some balances so the reports have content.
        $this->account($parent, '1000', 'Bank', 'assets', 500);
        $this->account($parent, '3000', 'Capital', 'equity', 500);
        $this->account($sub, '1000', 'Bank', 'assets', 200);
        $this->account($sub, '3000', 'Capital', 'equity', 200);

        $this->actingAs($user)->get(route('companies.group.balance-sheet', $parent))->assertOk()->assertSee('Non-controlling interests');
        $this->actingAs($user)->get(route('companies.group.income-statement', $parent))->assertOk()->assertSee('attributable to');

        // Record a balanced elimination journal.
        $this->actingAs($user)->get(route('companies.group.eliminations', $parent))->assertOk();
        $this->actingAs($user)
            ->post(route('companies.group.eliminations.store', $parent), [
                'elimination_date' => '2026-06-30',
                'type' => 'intercompany_balance',
                'description' => 'Eliminate intragroup loan',
                'lines' => [
                    ['bucket' => 'current_liabilities', 'debit' => 50, 'credit' => 0],
                    ['bucket' => 'current_assets', 'debit' => 0, 'credit' => 50],
                ],
            ])
            ->assertRedirect(route('companies.group.eliminations', $parent));

        $this->assertDatabaseHas('group_eliminations', [
            'company_id' => $parent->id,
            'description' => 'Eliminate intragroup loan',
        ]);
    }

    public function test_non_parent_cannot_view_consolidated_reports(): void
    {
        $user = User::factory()->create();
        $solo = $this->makeCompany($user, 'Solo Co');

        $this->actingAs($user)->get(route('companies.group.balance-sheet', $solo))->assertNotFound();
        $this->actingAs($user)->get(route('companies.group.income-statement', $solo))->assertNotFound();
    }

    private function postProfit(Company $c, User $user, float $revenue, float $expense): void
    {
        $bank = $this->account($c, '1000', 'Bank', 'assets');
        $income = $this->account($c, '4000', 'Sales', 'income');
        $cogs = $this->account($c, '5000', 'Cost of sales', 'expenses');

        $txn = Transaction::create([
            'company_id' => $c->id,
            'user_id' => $user->id,
            'transaction_date' => '2026-06-01',
            'description' => 'Trading',
            'status' => 'posted',
        ]);

        // Revenue: Dr Bank, Cr Sales
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $bank->id, 'type' => 'debit', 'amount' => $revenue]);
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $income->id, 'type' => 'credit', 'amount' => $revenue]);
        // Expense: Dr COGS, Cr Bank
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $cogs->id, 'type' => 'debit', 'amount' => $expense]);
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $bank->id, 'type' => 'credit', 'amount' => $expense]);
    }
}
