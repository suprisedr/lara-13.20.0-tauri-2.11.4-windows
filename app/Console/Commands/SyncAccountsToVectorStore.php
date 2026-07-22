<?php

namespace App\Console\Commands;

use App\Jobs\EmbedAccountJob;
use App\Models\ChartOfAccount;
use Illuminate\Console\Command;

class SyncAccountsToVectorStore extends Command
{
    protected $signature = 'vectors:sync-accounts
        {--limit=1000 : Max accounts to enqueue}
        {--force : Re-embed accounts that are already embedded}
        {--sync : Run embeds inline instead of queueing}';

    protected $description = 'Embed chart-of-account records into the pgvector store via Gemini.';

    public function handle(): int
    {
        $limit  = (int) $this->option('limit');
        $force  = (bool) $this->option('force');
        $sync   = (bool) $this->option('sync');

        $query = ChartOfAccount::query()->orderBy('id')->limit($limit);

        if (! $force) {
            $query->where('is_embedded', false);
        }

        $count = 0;
        $query->each(function (ChartOfAccount $account) use (&$count, $sync) {
            if ($sync) {
                EmbedAccountJob::dispatchSync($account->id);
            } else {
                EmbedAccountJob::dispatch($account->id);
            }
            $count++;
        });

        $this->info(($sync ? 'Embedded' : 'Queued')." {$count} chart-of-account record(s).");
        return self::SUCCESS;
    }
}
