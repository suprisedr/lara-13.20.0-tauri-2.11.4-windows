<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_customer_for_their_company(): void
    {
        $user = User::factory()->create();
        $company = Company::create([
            'user_id' => $user->id,
            'registered_name' => 'Acme Inc',
            'company_type' => 'sole_proprietor',
            'financial_year_end_month' => 12,
        ]);

        $response = $this->actingAs($user)->post(route('companies.customers.store', $company), [
            'name' => 'Acme Customer',
            'email' => 'customer@example.com',
            'address' => '123 Example Street',
        ]);

        $response->assertRedirect(route('companies.customers.index', $company));
        $this->assertDatabaseHas('customers', [
            'company_id' => $company->id,
            'name' => 'Acme Customer',
            'email' => 'customer@example.com',
            'address' => '123 Example Street',
            'is_active' => true,
        ]);
    }

    public function test_invoice_create_attaches_existing_customer(): void
    {
        $user = User::factory()->create();
        $company = Company::create([
            'user_id' => $user->id,
            'registered_name' => 'Acme Inc',
            'company_type' => 'sole_proprietor',
            'financial_year_end_month' => 12,
        ]);

        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Acme Customer',
            'email' => 'customer@example.com',
            'address' => '123 Example Street',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'company_id' => $company->id,
            'name' => 'Service Charge',
            'unit_price' => 100.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('companies.invoices.store', $company), [
            'invoice_number' => 'INV-0001',
            'customer_id' => $customer->id,
            'customer_name' => 'Should Be Ignored',
            'customer_email' => 'ignored@example.com',
            'customer_address' => 'Ignored Address',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => 'draft',
            'notes' => 'Test invoice',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_address' => $customer->address,
        ]);
    }

    public function test_invoice_create_with_new_email_creates_a_customer(): void
    {
        $user = User::factory()->create();
        $company = Company::create([
            'user_id' => $user->id,
            'registered_name' => 'Acme Inc',
            'company_type' => 'sole_proprietor',
            'financial_year_end_month' => 12,
        ]);

        $item = InventoryItem::create([
            'company_id' => $company->id,
            'name' => 'Service Charge',
            'unit_price' => 100.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('companies.invoices.store', $company), [
            'invoice_number' => 'INV-0001',
            'customer_name' => 'New Customer',
            'customer_email' => 'new@example.com',
            'customer_address' => 'New Address',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => 'draft',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertRedirect();

        $customer = Customer::where('company_id', $company->id)->where('email', 'new@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertSame('New Customer', $customer->name);

        $this->assertDatabaseHas('invoices', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'customer_email' => 'new@example.com',
        ]);
    }

    public function test_invoice_create_with_existing_email_attaches_existing_customer(): void
    {
        $user = User::factory()->create();
        $company = Company::create([
            'user_id' => $user->id,
            'registered_name' => 'Acme Inc',
            'company_type' => 'sole_proprietor',
            'financial_year_end_month' => 12,
        ]);

        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Existing Customer',
            'email' => 'existing@example.com',
            'address' => 'Existing Address',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'company_id' => $company->id,
            'name' => 'Service Charge',
            'unit_price' => 100.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('companies.invoices.store', $company), [
            'invoice_number' => 'INV-0002',
            'customer_name' => 'Typed Differently',
            'customer_email' => 'existing@example.com',
            'customer_address' => 'Typed Address',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => 'draft',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertSame(1, Customer::where('company_id', $company->id)->where('email', 'existing@example.com')->count());

        $this->assertDatabaseHas('invoices', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
        ]);
    }

    public function test_invoice_create_requires_customer_email(): void
    {
        $user = User::factory()->create();
        $company = Company::create([
            'user_id' => $user->id,
            'registered_name' => 'Acme Inc',
            'company_type' => 'sole_proprietor',
            'financial_year_end_month' => 12,
        ]);

        $item = InventoryItem::create([
            'company_id' => $company->id,
            'name' => 'Service Charge',
            'unit_price' => 100.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('companies.invoices.store', $company), [
            'invoice_number' => 'INV-0003',
            'customer_name' => 'No Email Customer',
            'invoice_date' => now()->format('Y-m-d'),
            'status' => 'draft',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('customer_email');
    }
}
