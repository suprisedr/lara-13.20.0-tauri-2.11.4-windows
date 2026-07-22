<?php

namespace App\Listeners;

use App\Ai\Agents\ChartOfAccountsAgent;
use App\Events\CompanyOnboarded;
use App\Models\ChartOfAccount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Laravel\Ai\Exceptions\RateLimitedException;

class GenerateChartOfAccounts implements ShouldQueue
{
    use InteractsWithQueue;

    /** Maximum number of attempts before the job is failed permanently. */
    public int $tries = 5;

    /** Seconds to wait before the first retry. */
    public int $backoff = 60;

    /**
     * Calculate per-attempt backoff: 60s, 120s, 240s, 480s, 960s.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 120, 240, 480, 960];
    }

    /**
     * Handle the event.
     */
    public function handle(CompanyOnboarded $event): void
    {
        $company = $event->company;

        // Skip if accounts have already been generated for this company.
        if ($company->chartOfAccounts()->exists()) {
            return;
        }

        $response = (new ChartOfAccountsAgent($company))->prompt(
            'Generate a complete, industry-appropriate chart of accounts for this business.'
        );

        $accounts = collect($response['accounts'])->map(fn(array $account) => [
            'company_id'   => $company->id,
            'account_code' => $account['account_code'],
            'account_name' => $account['account_name'],
            'account_type' => $account['account_type'],
            'category'     => $account['category'] ?? null,
            'description'  => $account['description'] ?? null,
            'parent_code'     => $account['parent_code'] ?? null,
            'opening_balance' => $account['opening_balance'] ?? 0.00,
            'is_active'       => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ])->all();

        ChartOfAccount::insert($accounts);

        // Resolve parent_code → parent_id now that all accounts have IDs.
        $codeToId = ChartOfAccount::where('company_id', $company->id)
            ->pluck('id', 'account_code');

        ChartOfAccount::where('company_id', $company->id)
            ->whereNotNull('parent_code')
            ->get()
            ->each(function (ChartOfAccount $account) use ($codeToId): void {
                $parentId = $codeToId->get($account->parent_code);
                if ($parentId) {
                    $account->updateQuietly(['parent_id' => $parentId]);
                }
            });
    }

    /**
     * Handle a job failure — re-release with a longer delay when rate limited.
     */
    public function failed(CompanyOnboarded $event, \Throwable $exception): void
    {
        if ($exception instanceof RateLimitedException && $this->attempts() < $this->tries) {
            $retryAfter = $exception->retryAfter ?? 60;
            $this->release($retryAfter);
        }
    }
}
