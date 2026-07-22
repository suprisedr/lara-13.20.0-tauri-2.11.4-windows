<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompanyWithAccounts(): array
    {
        $user = User::factory()->create();
        $company = Company::create([
            'user_id' => $user->id,
            'registered_name' => 'Acme Inc',
            'company_type' => 'sole_proprietor',
            'financial_year_end_month' => 12,
        ]);

        $bank = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '1000',
            'account_name' => 'Bank',
            'account_type' => 'assets',
        ]);

        $sales = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '4000',
            'account_name' => 'Sales',
            'account_type' => 'revenue',
        ]);

        return [$user, $company, $bank, $sales];
    }

    public function test_transactions_page_renders_with_new_transaction_modal(): void
    {
        [$user, $company] = $this->makeCompanyWithAccounts();

        $response = $this->actingAs($user)->get(route('companies.transactions', $company));

        $response->assertOk();
        $response->assertSee('New Transaction');
        $response->assertSee('Save transaction');
    }

    public function test_user_can_record_a_balanced_transaction(): void
    {
        [$user, $company, $bank, $sales] = $this->makeCompanyWithAccounts();

        $response = $this->actingAs($user)->post(route('companies.transactions.store', $company), [
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Cash sale',
            'reference' => 'INV-001',
            'status' => 'posted',
            'lines' => [
                ['chart_of_account_id' => $bank->id, 'type' => 'debit', 'amount' => '150.00'],
                ['chart_of_account_id' => $sales->id, 'type' => 'credit', 'amount' => '150.00'],
            ],
        ]);

        $response->assertRedirect(route('companies.transactions', $company));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'company_id' => $company->id,
            'description' => 'Cash sale',
            'reference' => 'INV-001',
            'status' => 'posted',
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'chart_of_account_id' => $bank->id,
            'type' => 'debit',
            'amount' => '150.00',
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'chart_of_account_id' => $sales->id,
            'type' => 'credit',
            'amount' => '150.00',
        ]);
    }

    public function test_unbalanced_transaction_is_rejected(): void
    {
        [$user, $company, $bank, $sales] = $this->makeCompanyWithAccounts();

        $response = $this->actingAs($user)->post(route('companies.transactions.store', $company), [
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Lopsided entry',
            'status' => 'draft',
            'lines' => [
                ['chart_of_account_id' => $bank->id, 'type' => 'debit', 'amount' => '150.00'],
                ['chart_of_account_id' => $sales->id, 'type' => 'credit', 'amount' => '100.00'],
            ],
        ]);

        $response->assertSessionHasErrors('transaction');
        $this->assertDatabaseMissing('transactions', ['description' => 'Lopsided entry']);
    }

    public function test_transaction_requires_at_least_two_lines(): void
    {
        [$user, $company, $bank] = $this->makeCompanyWithAccounts();

        $response = $this->actingAs($user)->post(route('companies.transactions.store', $company), [
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Single line',
            'status' => 'draft',
            'lines' => [
                ['chart_of_account_id' => $bank->id, 'type' => 'debit', 'amount' => '150.00'],
            ],
        ]);

        $response->assertSessionHasErrors('lines');
        $this->assertDatabaseMissing('transactions', ['description' => 'Single line']);
    }

    public function test_user_cannot_record_transaction_for_another_users_company(): void
    {
        [$owner, $company, $bank, $sales] = $this->makeCompanyWithAccounts();
        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)->post(route('companies.transactions.store', $company), [
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Hijack attempt',
            'status' => 'draft',
            'lines' => [
                ['chart_of_account_id' => $bank->id, 'type' => 'debit', 'amount' => '150.00'],
                ['chart_of_account_id' => $sales->id, 'type' => 'credit', 'amount' => '150.00'],
            ],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('transactions', ['description' => 'Hijack attempt']);
    }
}
