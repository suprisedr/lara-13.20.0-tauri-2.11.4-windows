<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\JournalLine;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompany(): Company
    {
        $user = User::factory()->create();

        return Company::create([
            'user_id' => $user->id,
            'registered_name' => 'Acme Inc',
            'company_type' => 'sole_proprietor',
            'financial_year_end_month' => 12,
        ]);
    }

    private function makeAccount(Company $company): ChartOfAccount
    {
        return ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '6000',
            'account_name' => 'Purchases',
            'account_type' => 'expense',
            'is_active' => true,
        ]);
    }

    private function makeJournalLine(Company $company, Supplier $supplier, ChartOfAccount $account, string $date, string $type, float $amount): JournalLine
    {
        $transaction = Transaction::create([
            'company_id' => $company->id,
            'user_id' => $company->user_id,
            'transaction_date' => $date,
            'description' => 'Test transaction',
            'reference' => 'REF-' . $date . '-' . $type,
            'status' => 'posted',
        ]);

        return JournalLine::create([
            'transaction_id' => $transaction->id,
            'chart_of_account_id' => $account->id,
            'supplier_id' => $supplier->id,
            'type' => $type,
            'amount' => $amount,
            'description' => 'Test line',
        ]);
    }

    public function test_user_can_create_a_supplier_for_their_company(): void
    {
        $user = User::factory()->create();
        $company = Company::create([
            'user_id' => $user->id,
            'registered_name' => 'Acme Inc',
            'company_type' => 'sole_proprietor',
            'financial_year_end_month' => 12,
        ]);

        $response = $this->actingAs($user)->post(route('companies.suppliers.store', $company), [
            'name' => 'Acme Supplier',
            'email' => 'supplier@example.com',
            'address' => '123 Example Street',
        ]);

        $response->assertRedirect(route('companies.suppliers.index', $company));
        $this->assertDatabaseHas('suppliers', [
            'company_id' => $company->id,
            'name' => 'Acme Supplier',
            'email' => 'supplier@example.com',
            'address' => '123 Example Street',
            'is_active' => true,
        ]);
    }

    public function test_index_renders_suppliers(): void
    {
        $company = $this->makeCompany();
        $user = $company->user;

        Supplier::create([
            'company_id' => $company->id,
            'name' => 'Acme Supplier',
            'email' => 'supplier@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('companies.suppliers.index', $company));

        $response->assertOk();
        $response->assertSee('Acme Supplier');
    }

    public function test_user_can_update_a_supplier(): void
    {
        $company = $this->makeCompany();
        $user = $company->user;

        $supplier = Supplier::create([
            'company_id' => $company->id,
            'name' => 'Acme Supplier',
            'email' => 'supplier@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->patch(route('companies.suppliers.update', [$company, $supplier]), [
            'name' => 'Acme Supplier Updated',
            'email' => 'supplier@example.com',
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('companies.suppliers.index', $company));
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Acme Supplier Updated',
            'is_active' => false,
        ]);
    }

    public function test_show_renders_supplier_profile(): void
    {
        $company = $this->makeCompany();
        $user = $company->user;
        $account = $this->makeAccount($company);

        $supplier = Supplier::create([
            'company_id' => $company->id,
            'name' => 'Acme Supplier',
            'email' => 'supplier@example.com',
            'is_active' => true,
        ]);

        $this->makeJournalLine($company, $supplier, $account, '2026-05-01', 'credit', 1000);
        $this->makeJournalLine($company, $supplier, $account, '2026-05-15', 'debit', 400);

        $response = $this->actingAs($user)->get(route('companies.suppliers.show', [$company, $supplier]));

        $response->assertOk();
        $response->assertSee('SUPPLIER PROFILE');
        $response->assertSee('1,000.00');
        $response->assertSee('400.00');
        $response->assertSee('600.00');
    }

    public function test_statement_includes_opening_balance_and_running_balance(): void
    {
        $company = $this->makeCompany();
        $user = $company->user;
        $account = $this->makeAccount($company);

        $supplier = Supplier::create([
            'company_id' => $company->id,
            'name' => 'Acme Supplier',
            'email' => 'supplier@example.com',
            'is_active' => true,
        ]);

        // Before the statement period — contributes to opening balance.
        $this->makeJournalLine($company, $supplier, $account, '2026-01-01', 'credit', 1000);
        $this->makeJournalLine($company, $supplier, $account, '2026-01-15', 'debit', 400);

        // Within the statement period.
        $this->makeJournalLine($company, $supplier, $account, '2026-04-10', 'credit', 500);

        $response = $this->actingAs($user)->get(route('companies.suppliers.statement', [$company, $supplier]) . '?start_date=2026-03-01&end_date=2026-06-30');

        $response->assertOk();
        $response->assertSee('STATEMENT OF ACCOUNT');
        // Opening balance = 1000 - 400 = 600.
        $response->assertSee('600.00');
        // Closing balance = 600 + 500 = 1,100.00
        $response->assertSee('1,100.00');
    }

    public function test_statement_pdf_downloads(): void
    {
        $company = $this->makeCompany();
        $user = $company->user;
        $account = $this->makeAccount($company);

        $supplier = Supplier::create([
            'company_id' => $company->id,
            'name' => 'Acme Supplier',
            'email' => 'supplier@example.com',
            'is_active' => true,
        ]);

        $this->makeJournalLine($company, $supplier, $account, '2026-05-01', 'credit', 250);

        $response = $this->actingAs($user)->get(route('companies.suppliers.statement.pdf', [$company, $supplier]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_other_users_cannot_view_statement(): void
    {
        $company = $this->makeCompany();
        $otherUser = User::factory()->create();
        $supplier = Supplier::create([
            'company_id' => $company->id,
            'name' => 'Acme Supplier',
            'email' => 'supplier@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($otherUser)->get(route('companies.suppliers.statement', [$company, $supplier]));

        $response->assertForbidden();
    }
}
