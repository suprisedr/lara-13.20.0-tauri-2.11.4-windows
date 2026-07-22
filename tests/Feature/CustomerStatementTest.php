<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerStatementTest extends TestCase
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

    private function makeInvoice(Company $company, Customer $customer, string $invoiceDate, string $status, float $amount): Invoice
    {
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-' . $customer->id . '-' . $invoiceDate,
            'customer_name' => $customer->name,
            'invoice_date' => $invoiceDate,
            'due_date' => $invoiceDate,
            'status' => $status,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Services rendered',
            'quantity' => 1,
            'unit_price' => $amount,
        ]);

        return $invoice;
    }

    public function test_statement_includes_opening_balance_and_running_balance(): void
    {
        $company = $this->makeCompany();
        $user = $company->user;
        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Acme Customer',
            'email' => 'customer@example.com',
            'is_active' => true,
        ]);

        // Before the statement period — contributes to opening balance.
        $oldInvoice = $this->makeInvoice($company, $customer, '2026-01-01', 'partially_paid', 1000);
        InvoicePayment::create([
            'invoice_id' => $oldInvoice->id,
            'user_id' => $user->id,
            'payment_date' => '2026-01-15',
            'amount' => 400,
        ]);

        // Within the statement period.
        $this->makeInvoice($company, $customer, '2026-04-10', 'pending', 500);

        $response = $this->actingAs($user)->get(route('companies.customers.statement', [$company, $customer]) . '?start_date=2026-03-01&end_date=2026-06-30');

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
        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Acme Customer',
            'email' => 'customer@example.com',
            'is_active' => true,
        ]);

        $this->makeInvoice($company, $customer, '2026-05-01', 'pending', 250);

        $response = $this->actingAs($user)->get(route('companies.customers.statement.pdf', [$company, $customer]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_other_users_cannot_view_statement(): void
    {
        $company = $this->makeCompany();
        $otherUser = User::factory()->create();
        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Acme Customer',
            'email' => 'customer@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($otherUser)->get(route('companies.customers.statement', [$company, $customer]));

        $response->assertForbidden();
    }
}
