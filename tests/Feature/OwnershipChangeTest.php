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

class OwnershipChangeTest extends TestCase
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

    private function postTrading(Company $c, User $user, float $revenue, float $expense, string $date): void
    {
        $bank = ChartOfAccount::firstOrCreate(
            ['company_id' => $c->id, 'account_code' => '1000'],
            ['account_name' => 'Bank', 'account_type' => 'assets', 'is_active' => true, 'opening_balance' => 0]
        );
        $income = ChartOfAccount::firstOrCreate(
            ['company_id' => $c->id, 'account_code' => '4000'],
            ['account_name' => 'Sales', 'account_type' => 'income', 'is_active' => true, 'opening_balance' => 0]
        );
        $cogs = ChartOfAccount::firstOrCreate(
            ['company_id' => $c->id, 'account_code' => '5000'],
            ['account_name' => 'Cost of sales', 'account_type' => 'expenses', 'is_active' => true, 'opening_balance' => 0]
        );

        $txn = Transaction::create([
            'company_id' => $c->id,
            'user_id' => $user->id,
            'transaction_date' => $date,
            'description' => 'Trading',
            'status' => 'posted',
        ]);
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $bank->id, 'type' => 'debit', 'amount' => $revenue]);
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $income->id, 'type' => 'credit', 'amount' => $revenue]);
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $cogs->id, 'type' => 'debit', 'amount' => $expense]);
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $bank->id, 'type' => 'credit', 'amount' => $expense]);
    }

    private function recordEvent(User $user, Company $parent, Company $sub, array $data): void
    {
        $this->actingAs($user)
            ->post(route('companies.group.ownership-events.store', $parent), array_merge([
                'subsidiary_company_id' => $sub->id,
            ], $data))
            ->assertRedirect(route('companies.group.ownership-events', $parent));
    }

    public function test_buying_more_interest_with_control_retained_is_an_equity_transaction(): void
    {
        $user = User::factory()->create();
        $parent = $this->makeCompany($user, 'Parent Holdings');
        $sub = $this->makeCompany($user, 'Sub Co');

        // Parent books reflect the cumulative investment of 340 (300 + 40).
        $this->account($parent, '1000', 'Bank', 'assets', 660);
        $this->account($parent, '1600', 'Investment in Sub', 'assets', 340);
        $this->account($parent, '3000', 'Share capital', 'equity', 1000);

        // Sub net assets 350.
        $this->account($sub, '1000', 'Bank', 'assets', 450);
        $this->account($sub, '2000', 'Payables', 'liabilities', 100);
        $this->account($sub, '3000', 'Share capital', 'equity', 350);

        // Acquire 80% for 300, then buy a further 10% for 40 — net assets 350 throughout.
        $this->recordEvent($user, $parent, $sub, [
            'event_date' => '2026-01-01', 'type' => 'acquisition',
            'ownership_before' => 0, 'ownership_after' => 80,
            'consideration' => 300, 'equity_at_event' => 350,
        ]);
        $this->recordEvent($user, $parent, $sub, [
            'event_date' => '2026-06-01', 'type' => 'increase',
            'ownership_before' => 80, 'ownership_after' => 90,
            'consideration' => 40, 'equity_at_event' => 350,
        ]);

        $r = (new ConsolidationService())->balanceSheet($parent->fresh(), '2026-12-31');

        // Goodwill frozen at acquisition: 300 − 80% × 350 = 20 (NOT 340 − 90% × 350).
        $this->assertEqualsWithDelta(20.0, $r['goodwill'], 0.01);
        // NCI at current 10%: 0.10 × 350 = 35.
        $this->assertEqualsWithDelta(35.0, $r['nci'], 0.01);
        // Premium paid to NCI (40 paid for 35 of net assets) → −5 equity reserve.
        $this->assertEqualsWithDelta(-5.0, $r['ownershipReserve'], 0.01);
        // Assets = 1000 + 450 − 340 investment + 20 goodwill = 1130.
        $this->assertEqualsWithDelta(1130.0, $r['totalAssets'], 0.01);
        $this->assertEqualsWithDelta(100.0, $r['totalLiabilities'], 0.01);
        // Owners' equity = 1000 standalone − 5 premium = 995.
        $this->assertEqualsWithDelta(995.0, $r['ownersEquity'], 0.01);
        $this->assertEqualsWithDelta($r['totalAssets'], $r['totalLiabilities'] + $r['totalEquity'], 0.01);
    }

    public function test_step_acquisition_recognises_remeasurement_gain_in_profit(): void
    {
        $user = User::factory()->create();
        $parent = $this->makeCompany($user, 'Parent Holdings');
        $sub = $this->makeCompany($user, 'Target Co');

        // Previously held 30% (associate) carried at 100; FV at control date 150.
        $this->recordEvent($user, $parent, $sub, [
            'event_date' => '2026-06-30', 'type' => 'acquisition',
            'ownership_before' => 30, 'ownership_after' => 70,
            'consideration' => 200, 'equity_at_event' => 400,
            'fair_value_previously_held' => 150, 'carrying_previously_held' => 100,
        ]);

        $r = (new ConsolidationService())->incomeStatement($parent->fresh(), '2026-01-01', '2026-12-31');

        // Remeasurement gain = 150 − 100 = 50, recognised in P&L, attributable to owners.
        $this->assertEqualsWithDelta(50.0, $r['remeasurementGain'], 0.01);
        $this->assertEqualsWithDelta(50.0, $r['profit'], 0.01);
        $this->assertEqualsWithDelta(0.0, $r['nciProfit'], 0.01);
        $this->assertEqualsWithDelta(50.0, $r['ownersProfit'], 0.01);
    }

    public function test_loss_of_control_deconsolidates_and_recognises_disposal_gain(): void
    {
        $user = User::factory()->create();
        $parent = $this->makeCompany($user, 'Parent Holdings');
        $sub = $this->makeCompany($user, 'Sold Co');

        $this->account($parent, '1000', 'Bank', 'assets', 1000);
        $this->account($parent, '3000', 'Share capital', 'equity', 1000);
        $this->account($sub, '1000', 'Bank', 'assets', 500);
        $this->account($sub, '3000', 'Share capital', 'equity', 500);

        $this->recordEvent($user, $parent, $sub, [
            'event_date' => '2026-01-01', 'type' => 'acquisition',
            'ownership_before' => 0, 'ownership_after' => 80,
            'consideration' => 300, 'equity_at_event' => 400,
        ]);
        // Dispose entire holding on 30 Jun for 500; goodwill carried 20.
        $this->recordEvent($user, $parent, $sub, [
            'event_date' => '2026-06-30', 'type' => 'disposal',
            'ownership_before' => 80, 'ownership_after' => 0,
            'consideration' => 500, 'equity_at_event' => 400,
            'goodwill_derecognised' => 20,
        ]);

        $service = new ConsolidationService();

        // After disposal the subsidiary is deconsolidated.
        $bs = $service->balanceSheet($parent->fresh(), '2026-12-31');
        $this->assertCount(1, $bs['worksheet']); // parent only
        $this->assertEmpty($bs['subDetails']);

        // Disposal gain = 500 proceeds − 80% × 400 net assets − 20 goodwill = 160.
        $is = $service->incomeStatement($parent->fresh(), '2026-01-01', '2026-12-31');
        $this->assertEqualsWithDelta(160.0, $is['disposalGain'], 0.01);
        $this->assertEqualsWithDelta(160.0, $is['profit'], 0.01);
    }

    public function test_nci_profit_is_time_apportioned_across_ownership_segments(): void
    {
        $user = User::factory()->create();
        $parent = $this->makeCompany($user, 'Parent Holdings');
        $sub = $this->makeCompany($user, 'Sub Co');

        // Control obtained before the period at 80%, increased to 90% on 1 July.
        $this->recordEvent($user, $parent, $sub, [
            'event_date' => '2025-01-01', 'type' => 'acquisition',
            'ownership_before' => 0, 'ownership_after' => 80,
            'consideration' => 300, 'equity_at_event' => 350,
        ]);
        $this->recordEvent($user, $parent, $sub, [
            'event_date' => '2026-07-01', 'type' => 'increase',
            'ownership_before' => 80, 'ownership_after' => 90,
            'consideration' => 50, 'equity_at_event' => 400,
        ]);

        // Profit 400 in H1 (NCI 20%) and 400 in H2 (NCI 10%).
        $this->postTrading($sub, $user, 1000, 600, '2026-03-31');
        $this->postTrading($sub, $user, 1000, 600, '2026-09-30');

        $r = (new ConsolidationService())->incomeStatement($parent->fresh(), '2026-01-01', '2026-12-31');

        // NCI = 20% × 400 + 10% × 400 = 80 + 40 = 120.
        $this->assertEqualsWithDelta(120.0, $r['nciProfit'], 0.01);
        $this->assertEqualsWithDelta(800.0, $r['profit'], 0.01);
        $this->assertEqualsWithDelta(680.0, $r['ownersProfit'], 0.01);
    }
}
