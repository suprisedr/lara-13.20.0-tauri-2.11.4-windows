<?php

namespace App\Console\Commands;

use App\Ai\Agents\ChartOfAccountsAgent;
use App\Models\ChartOfAccount;
use App\Models\Company;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:regenerate-chart-of-accounts {company_id : The ID of the company}')]
#[Description('Generate or regenerate the AI-powered chart of accounts for a company')]
class RegenerateChartOfAccounts extends Command
{
    public function handle(): int
    {
        $company = Company::find($this->argument('company_id'));

        if (! $company) {
            $this->error("Company not found.");
            return self::FAILURE;
        }

        $existingCount = $company->chartOfAccounts()->count();

        if ($existingCount > 0) {
            if (! $this->confirm("Company '{$company->name}' already has {$existingCount} accounts. Delete and regenerate?")) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
            $company->chartOfAccounts()->delete();
            $this->info("Deleted {$existingCount} existing accounts.");
        }

        $this->info("Generating chart of accounts for company #{$company->id} (industry: {$company->industry})...");

        $response = (new ChartOfAccountsAgent($company))->prompt(
            'Generate a complete, industry-appropriate chart of accounts for this business.'
        );

        $accounts = collect($response['accounts'])->map(fn (array $account) => [
            'company_id'        => $company->id,
            'account_code'      => $account['account_code'],
            'account_name'      => $account['account_name'],
            'account_type'      => $account['account_type'],
            'category'          => $account['category'] ?? null,
            'cash_flow_category' => $account['cash_flow_category'] ?? null,
            'is_contra'         => $account['is_contra'] ?? false,
            'description'       => $account['description'] ?? null,
            'parent_code'       => $account['parent_code'] ?? null,
            'opening_balance'   => $account['opening_balance'] ?? 0.00,
            'is_active'         => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ])->all();

        ChartOfAccount::insert($accounts);

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

        $this->info("Successfully generated " . count($accounts) . " accounts.");

        return self::SUCCESS;
    }
}
