<?php

namespace App\Observers;

use App\Jobs\EmbedAccountJob;
use App\Models\ChartOfAccount;

class ChartOfAccountObserver
{
    public function created(ChartOfAccount $account): void
    {
        EmbedAccountJob::dispatch($account->id);
    }

    public function updated(ChartOfAccount $account): void
    {
        if ($account->wasChanged(['account_code', 'account_name', 'account_type', 'category', 'description', 'is_contra'])) {
            $account->forceFill(['is_embedded' => false, 'embedded_at' => null])->saveQuietly();
            EmbedAccountJob::dispatch($account->id);
        }
    }
}
