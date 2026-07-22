<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinancialStatementNotesSeeder;
use App\Services\NoteFigureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteFiguresTest extends TestCase
{
    use RefreshDatabase;

    private function company(User $user): Company
    {
        return Company::create([
            'user_id' => $user->id,
            'registered_name' => 'Notes Co',
            'company_type' => 'pty_ltd',
            'financial_year_end_month' => 12,
        ]);
    }

    private function account(Company $c, string $code, string $name, string $type): ChartOfAccount
    {
        return ChartOfAccount::create([
            'company_id' => $c->id,
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => $type,
            'is_active' => true,
            'opening_balance' => 0,
        ]);
    }

    private function journal(Company $c, User $u, string $date, ChartOfAccount $dr, ChartOfAccount $cr, float $amt): void
    {
        $txn = Transaction::create([
            'company_id' => $c->id, 'user_id' => $u->id,
            'transaction_date' => $date, 'description' => 'x', 'status' => 'posted',
        ]);
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $dr->id, 'type' => 'debit', 'amount' => $amt]);
        JournalLine::create(['transaction_id' => $txn->id, 'chart_of_account_id' => $cr->id, 'type' => 'credit', 'amount' => $amt]);
    }

    public function test_note_figures_sum_linked_accounts_with_sign(): void
    {
        $user = User::factory()->create();
        $company = $this->company($user);
        (new FinancialStatementNotesSeeder())->seed($company);

        $bank = $this->account($company, '1000', 'Bank', 'assets');
        $sales = $this->account($company, '4000', 'Sale of goods', 'income');
        $royalties = $this->account($company, '4100', 'Royalties', 'income');
        $rebates = $this->account($company, '4200', 'Rebates', 'income');

        // 2025 income: sales 6,000; royalties 120; rebates 100.
        $this->journal($company, $user, '2025-03-01', $bank, $sales, 6000);
        $this->journal($company, $user, '2025-03-02', $bank, $royalties, 120);
        $this->journal($company, $user, '2025-03-03', $bank, $rebates, 100);

        $revenueNote = $company->financialStatementNotes()->where('slug', 'revenue')->firstOrFail();

        // Link: + sales, + royalties, − rebates.
        $this->actingAs($user)->post(route('companies.notes-to-afs.lines.store', [$company, $revenueNote]), [
            'chart_of_account_id' => $sales->id, 'sign' => '1',
        ])->assertRedirect();
        $this->actingAs($user)->post(route('companies.notes-to-afs.lines.store', [$company, $revenueNote]), [
            'chart_of_account_id' => $royalties->id, 'sign' => '1', 'label' => 'Royalties — patents',
        ])->assertRedirect();
        $this->actingAs($user)->post(route('companies.notes-to-afs.lines.store', [$company, $revenueNote]), [
            'chart_of_account_id' => $rebates->id, 'sign' => '-1',
        ])->assertRedirect();

        $fig = (new NoteFigureService())->figuresFor($company, $revenueNote->fresh(), '2025-01-01', '2025-12-31');

        $this->assertTrue($fig['has']);
        $this->assertCount(3, $fig['rows']);
        // 6000 + 120 - 100 = 6020.
        $this->assertEqualsWithDelta(6020.0, $fig['total_current'], 0.01);
        $this->assertEqualsWithDelta(0.0, $fig['total_prior'], 0.01);

        // Custom label is used.
        $labels = array_column($fig['rows'], 'label');
        $this->assertContains('Royalties — patents', $labels);
    }

    public function test_note_show_page_lists_and_removes_links(): void
    {
        $user = User::factory()->create();
        $company = $this->company($user);
        (new FinancialStatementNotesSeeder())->seed($company);
        $acct = $this->account($company, '4000', 'Sales', 'income');
        $note = $company->financialStatementNotes()->where('slug', 'revenue')->firstOrFail();

        $this->actingAs($user)->post(route('companies.notes-to-afs.lines.store', [$company, $note]), [
            'chart_of_account_id' => $acct->id, 'sign' => '1',
        ])->assertRedirect();

        $link = $note->accountLinks()->firstOrFail();

        $this->actingAs($user)->get(route('companies.notes-to-afs.show', [$company, $note]))
            ->assertOk()
            ->assertSee('Sales');

        $this->actingAs($user)->delete(route('companies.notes-to-afs.lines.destroy', [$company, $note, $link]))
            ->assertRedirect();

        $this->assertDatabaseMissing('note_account_links', ['id' => $link->id]);
    }
}
