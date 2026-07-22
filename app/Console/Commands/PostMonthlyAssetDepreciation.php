<?php

namespace App\Console\Commands;

use App\Jobs\PostAssetDepreciationJob;
use App\Models\Asset;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class PostMonthlyAssetDepreciation extends Command
{
    protected $signature = 'assets:post-monthly-depreciation
        {--month= : Month to post (YYYY-MM). Defaults to the previous month.}
        {--company= : Limit to a single company id.}';

    protected $description = 'Queue per-asset monthly depreciation postings (CB-guarded).';

    public function handle(): int
    {
        $month = $this->option('month')
            ? CarbonImmutable::createFromFormat('Y-m', (string) $this->option('month'))->endOfMonth()
            : now()->subMonthNoOverflow()->endOfMonth()->toImmutable();

        $query = Asset::active()
            ->whereHas('ppeClass')
            ->where(function ($q) use ($month) {
                $q->whereNull('last_depreciation_posted_on')
                  ->orWhere('last_depreciation_posted_on', '<', $month->format('Y-m-d'));
            });
        if ($this->option('company')) {
            $query->where('company_id', (int) $this->option('company'));
        }

        $count = 0;
        $query->orderBy('id')->each(function (Asset $asset) use (&$count, $month) {
            PostAssetDepreciationJob::dispatch($asset->id, $month->format('Y-m-d'));
            $count++;
        });

        $this->info("Queued depreciation for {$count} asset(s) — period {$month->format('M Y')}.");
        return self::SUCCESS;
    }
}
