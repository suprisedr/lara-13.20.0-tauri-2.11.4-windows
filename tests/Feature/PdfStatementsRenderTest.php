<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfStatementsRenderTest extends TestCase
{
    use RefreshDatabase;

    private function setupCo(): array
    {
        $user = User::factory()->create();
        $company = Company::create([
            'user_id' => $user->id,
            'registered_name' => 'Render Co',
            'company_type' => 'pty_ltd',
            'financial_year_end_month' => 12,
        ]);

        $mk = fn (string $code, string $name, string $type) => ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => $type,
            'is_active' => true,
            'opening_balance' => 0,
        ]);

        $bank = $mk('1000', 'Bank', 'assets');
        $equity = $mk('3000', 'Capital', 'equity');
        $sales = $mk('4000', 'Sales', 'income');
        $rent = $mk('6000', 'Rent', 'expenses');

        $txn = Transaction::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Trade',
            'status' => 'posted',
        ]);
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $bank->id, 'type' => 'debit', 'amount' => 500]);
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $sales->id, 'type' => 'credit', 'amount' => 500]);

        return [$user, $company];
    }

    public function test_single_statement_pdfs_render(): void
    {
        [$user, $company] = $this->setupCo();

        foreach (['balance-sheet', 'income-statement', 'cash-flow', 'changes-in-equity'] as $statement) {
            $response = $this->actingAs($user)
                ->get(route("companies.reports.{$statement}.pdf", $company));

            $response->assertOk();
            $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        }
    }

    public function test_changes_in_equity_web_report_renders(): void
    {
        [$user, $company] = $this->setupCo();

        $this->actingAs($user)
            ->get(route('companies.reports.changes-in-equity', $company))
            ->assertOk()
            ->assertSee('Statement of Changes in Equity');
    }
}
