<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\EclRateSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\AgeAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgeAnalysisTest extends TestCase
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

    private function makeInvoice(Company $company, Customer $customer, string $dueDate, string $status, float $amount): Invoice
    {
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-' . $customer->id . '-' . $dueDate,
            'customer_name' => $customer->name,
            'invoice_date' => $dueDate,
            'due_date' => $dueDate,
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

    public function test_age_analysis_buckets_open_invoices_by_days_overdue(): void
    {
        $company = $this->makeCompany();
        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Acme Customer',
            'email' => 'customer@example.com',
            'is_active' => true,
        ]);

        $asOf = '2026-06-12';

        // Current — not yet due.
        $this->makeInvoice($company, $customer, '2026-06-20', 'pending', 100);
        // 31-60 days overdue.
        $this->makeInvoice($company, $customer, '2026-05-01', 'overdue', 200);
        // 61-90 days overdue.
        $this->makeInvoice($company, $customer, '2026-03-20', 'overdue', 300);
        // 91+ days overdue.
        $this->makeInvoice($company, $customer, '2026-01-01', 'overdue', 400);
        // Paid invoices should be excluded entirely.
        $this->makeInvoice($company, $customer, '2026-06-01', 'paid', 999);

        $analyses = (new AgeAnalysisService())->generate($company, $asOf);

        $this->assertCount(1, $analyses);

        $analysis = $analyses->first();

        $this->assertEquals(100, (float) $analysis->current_amount);
        $this->assertEquals(200, (float) $analysis->days_31_60);
        $this->assertEquals(300, (float) $analysis->days_61_90);
        $this->assertEquals(400, (float) $analysis->days_91_plus);
        $this->assertEquals(1000, (float) $analysis->total_outstanding);

        $this->assertDatabaseHas('customer_age_analyses', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'as_of_date' => $asOf . ' 00:00:00',
        ]);
    }

    public function test_age_analysis_applies_ecl_provision_matrix(): void
    {
        $company = $this->makeCompany();
        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Acme Customer',
            'email' => 'customer@example.com',
            'is_active' => true,
        ]);

        EclRateSetting::create([
            'company_id' => $company->id,
            'current_rate' => 0.01,
            'days_31_60_rate' => 0.05,
            'days_61_90_rate' => 0.25,
            'days_91_plus_rate' => 0.50,
        ]);

        $asOf = '2026-06-12';

        $this->makeInvoice($company, $customer, '2026-06-20', 'pending', 1000);
        $this->makeInvoice($company, $customer, '2026-01-01', 'overdue', 1000);

        $analysis = (new AgeAnalysisService())->generate($company, $asOf)->first();

        $this->assertEquals(10, (float) $analysis->ecl_current);
        $this->assertEquals(500, (float) $analysis->ecl_91_plus);
        $this->assertEquals(510, (float) $analysis->total_ecl);
    }

    public function test_age_analysis_report_renders_for_company_owner(): void
    {
        $company = $this->makeCompany();
        $user = $company->user;

        Customer::create([
            'company_id' => $company->id,
            'name' => 'Acme Customer',
            'email' => 'customer@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('companies.reports.age-analysis', $company));

        $response->assertOk();
        $response->assertSee('Debtors Age Analysis');
        $response->assertSee('Expected Credit Loss');
    }

    public function test_owner_can_update_ecl_rates(): void
    {
        $company = $this->makeCompany();
        $user = $company->user;

        $response = $this->actingAs($user)->post(route('companies.reports.age-analysis.ecl-rates', $company), [
            'current_rate' => 2,
            'days_31_60_rate' => 10,
            'days_61_90_rate' => 30,
            'days_91_plus_rate' => 60,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('ecl_rate_settings', [
            'company_id' => $company->id,
            'current_rate' => 0.02,
            'days_31_60_rate' => 0.10,
            'days_61_90_rate' => 0.30,
            'days_91_plus_rate' => 0.60,
        ]);
    }
}
