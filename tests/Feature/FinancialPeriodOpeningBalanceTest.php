<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialPeriodOpeningBalanceTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompany(User $user): Company
    {
        return Company::create([
            'user_id' => $user->id,
            'registered_name' => 'Period Co',
            'company_type' => 'pty_ltd',
            'financial_year_end_month' => 12, // FY = 1 Jan – 31 Dec
        ]);
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

    private function journal(Company $c, User $u, string $date, ChartOfAccount $dr, ChartOfAccount $cr, float $amt): void
    {
        $txn = Transaction::create([
            'company_id' => $c->id,
            'user_id' => $u->id,
            'transaction_date' => $date,
            'description' => 'Test',
            'status' => 'posted',
        ]);
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $dr->id, 'type' => 'debit', 'amount' => $amt]);
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $cr->id, 'type' => 'credit', 'amount' => $amt]);
    }

    public function test_first_period_is_seeded_from_legacy_opening_balance_column(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany($user);
        $bank = $this->account($company, '1000', 'Bank', 'assets', 500.0);
        $equity = $this->account($company, '3000', 'Capital', 'equity', 500.0);

        $this->actingAs($user)
            ->get(route('companies.opening-balances.edit', $company))
            ->assertOk();

        $period = $company->financialPeriods()->first();
        $this->assertNotNull($period);
        $this->assertEqualsWithDelta(500.0, (float) $period->openingBalances()->where('chart_of_account_id', $bank->id)->value('amount'), 0.001);
        $this->assertEqualsWithDelta(500.0, (float) $period->openingBalances()->where('chart_of_account_id', $equity->id)->value('amount'), 0.001);
    }

    public function test_open_next_period_carries_forward_closing_balances(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany($user);
        // Travel so "now" sits in the 2025 financial year.
        $this->travelTo('2025-06-15');

        $bank = $this->account($company, '1000', 'Bank', 'assets', 100.0);
        $equity = $this->account($company, '3000', 'Capital', 'equity', 100.0);
        $sales = $this->account($company, '4000', 'Sales', 'income');
        $expense = $this->account($company, '6000', 'Rent', 'expenses');

        // Trade within FY2025: Dr Bank / Cr Sales 300; Dr Rent / Cr Bank 50.
        $this->journal($company, $user, '2025-03-01', $bank, $sales, 300.0);
        $this->journal($company, $user, '2025-04-01', $expense, $bank, 50.0);

        // Ensure the first (2025) period exists, then open the next (2026) one.
        $this->actingAs($user)->get(route('companies.opening-balances.edit', $company))->assertOk();
        $this->actingAs($user)->post(route('companies.financial-periods.next', $company))->assertRedirect();

        $periods = $company->financialPeriods()->get();
        $this->assertCount(2, $periods);
        $next = $periods->last();
        $this->assertSame('2026-01-01', $next->start_date->toDateString());
        $this->assertSame('2026-12-31', $next->end_date->toDateString());

        $opening = fn (ChartOfAccount $a) => (float) $next->openingBalances()
            ->where('chart_of_account_id', $a->id)->value('amount');

        // Bank closing FY2025 = 100 opening + 300 - 50 = 350 → carried forward.
        $this->assertEqualsWithDelta(350.0, $opening($bank), 0.001);
        // Equity unchanged = 100 → carried forward.
        $this->assertEqualsWithDelta(100.0, $opening($equity), 0.001);
        // P&L accounts reset to zero in the new period (close to retained earnings).
        $this->assertEqualsWithDelta(0.0, $opening($sales), 0.001);
        $this->assertEqualsWithDelta(0.0, $opening($expense), 0.001);

        $this->travelBack();
    }

    public function test_balance_sheet_uses_period_opening_not_cumulative_history(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany($user);

        $bank = $this->account($company, '1000', 'Bank', 'assets');
        $equity = $this->account($company, '3000', 'Capital', 'equity');

        // Prior-year movement that should NOT be double-counted once period
        // openings exist: Dr Bank / Cr Capital 1000 in 2025.
        $this->journal($company, $user, '2025-05-01', $bank, $equity, 1000.0);

        // Create the 2026 period with carried-forward openings (Bank 1000, Capital 1000),
        // then add a 2026 movement: Dr Bank / Cr Capital 200.
        $period2025 = $company->financialPeriods()->create([
            'label' => 'FY2025', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31',
        ]);
        $period2025->openingBalances()->create(['chart_of_account_id' => $bank->id, 'amount' => 0]);
        $period2025->openingBalances()->create(['chart_of_account_id' => $equity->id, 'amount' => 0]);

        $period2026 = $company->financialPeriods()->create([
            'label' => 'FY2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
        ]);
        $period2026->openingBalances()->create(['chart_of_account_id' => $bank->id, 'amount' => 1000]);
        $period2026->openingBalances()->create(['chart_of_account_id' => $equity->id, 'amount' => 1000]);

        $this->journal($company, $user, '2026-02-01', $bank, $equity, 200.0);

        // Balance sheet as at mid-2026 should show Bank = 1000 (period opening) + 200
        // (in-period movement) = 1200 — NOT 1000 + 1000 + 200 (which double counts the
        // 2025 movement already embedded in the period opening).
        $response = $this->actingAs($user)
            ->get(route('companies.reports.balance-sheet', [$company, 'as_of_date' => '2026-06-30']));
        $response->assertOk();

        // Total assets should equal 1200.
        $this->assertStringContainsString('1,200', $response->getContent());
    }
}
