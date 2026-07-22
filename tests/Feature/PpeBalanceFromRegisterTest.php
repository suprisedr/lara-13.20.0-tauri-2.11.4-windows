<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PpeBalanceFromRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_sheet_ppe_uses_register_carrying_amount(): void
    {
        $user = User::factory()->create();
        $company = Company::create([
            'user_id' => $user->id,
            'registered_name' => 'PPE Co',
            'company_type' => 'pty_ltd',
            'financial_year_end_month' => 12,
        ]);

        // A PPE control account in the ledger (non-current asset, code >= 1500),
        // flagged is_ppe so the balance sheet replaces it with the register figure.
        $ppeAccount = ChartOfAccount::create([
            'company_id' => $company->id, 'account_code' => '1600',
            'account_name' => 'Property, plant and equipment', 'account_type' => 'assets',
            'is_active' => true, 'opening_balance' => 0, 'is_ppe' => true,
        ]);
        $capital = ChartOfAccount::create([
            'company_id' => $company->id, 'account_code' => '3000',
            'account_name' => 'Capital', 'account_type' => 'equity',
            'is_active' => true, 'opening_balance' => 0,
        ]);

        // Ledger PPE balance of 50,000 (Dr PPE / Cr Capital) — should NOT drive the
        // statement once is_ppe is set.
        $txn = Transaction::create([
            'company_id' => $company->id, 'user_id' => $user->id,
            'transaction_date' => '2025-06-01', 'description' => 'buy', 'status' => 'posted',
        ]);
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $ppeAccount->id, 'type' => 'debit', 'amount' => 50000]);
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $capital->id, 'type' => 'credit', 'amount' => 50000]);

        // Register asset: cost 100,000, 5-year life → NBV after a full year = 80,000.
        $company->assets()->create([
            'name' => 'Machine', 'acquisition_date' => '2025-01-01',
            'cost' => 100000, 'residual_value' => 0, 'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
        ]);

        $response = $this->actingAs($user)
            ->get(route('companies.reports.balance-sheet', [$company, 'as_of_date' => '2026-01-01']));

        $response->assertOk();
        $response->assertSee('Property, Plant and Equipment');
        // PPE shown at the register carrying amount (80,000)…
        $response->assertSee('80,000.00');
        // …and the ledger PPE balance is replaced, not added on top (would be 130,000).
        $response->assertDontSee('130,000.00');
    }
}
