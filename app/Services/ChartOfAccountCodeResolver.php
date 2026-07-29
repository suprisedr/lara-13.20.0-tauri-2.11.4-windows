<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 * Computes the next available account_code for a newly created chart of
 * account entry, following the same numeric ranges the onboarding AI agent
 * and the manual "add account" form both use, so every account created
 * through any path stays consistently numbered.
 */
class ChartOfAccountCodeResolver
{
    private const TYPE_RANGES = [
        'assets'      => [1000000, 1999000],
        'liabilities' => [2000000, 2999000],
        'equity'      => [3000000, 3999000],
        'income'      => [4000000, 4999000],
        'expenses'    => [5000000, 8999000],
    ];

    // IFRS expense bands — line up with the classification ranges the
    // statement of profit or loss (soci.blade.php) uses to group expenses.
    private const EXPENSE_RANGES = [
        'cost_of_sales' => [5000000, 5999000],
        'operating'     => [6000000, 6999000],
        'finance'       => [7000000, 7999000],
        'tax'           => [8000000, 8999000],
    ];

    /**
     * Next available top-level (parent/standalone) account_code for $type.
     */
    public function nextTopLevelCode(Company $company, string $type, ?string $expenseClass = null): string
    {
        [$min, $max] = ($type === 'expenses' && isset(self::EXPENSE_RANGES[$expenseClass]))
            ? self::EXPENSE_RANGES[$expenseClass]
            : self::TYPE_RANGES[$type];

        $existingCodes = $company->chartOfAccounts()
            ->whereNull('parent_id')
            ->whereBetween(DB::raw('CAST(account_code AS UNSIGNED)'), [$min, $max])
            ->pluck('account_code')
            ->map(fn ($c) => (int) $c)
            ->all();

        // Parents start at min+1000 (e.g. 1001000) and step by 1000.
        // min itself (e.g. 1000000) is the top-level type placeholder.
        $candidate = $min + 1000;
        while (in_array($candidate, $existingCodes, true) && $candidate <= $max) {
            $candidate += 1000;
        }

        return (string) $candidate;
    }

    /**
     * Next available child account_code beneath an existing parent account.
     */
    public function nextChildCode(Company $company, ChartOfAccount $parent): string
    {
        $parentCode = (int) $parent->account_code;
        $rangeMin = $parentCode + 1;
        $rangeMax = $parentCode + 999;

        $maxExisting = $company->chartOfAccounts()
            ->where('parent_id', $parent->id)
            ->whereBetween(DB::raw('CAST(account_code AS UNSIGNED)'), [$rangeMin, $rangeMax])
            ->max(DB::raw('CAST(account_code AS UNSIGNED)'));

        return (string) ($maxExisting ? ((int) $maxExisting + 1) : $rangeMin);
    }
}
