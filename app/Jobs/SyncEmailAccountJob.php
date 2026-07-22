<?php

namespace App\Jobs;

use App\Models\CompanyEmailAccount;
use App\Services\EmailSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncEmailAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public CompanyEmailAccount $account
    ) {}

    public function handle(EmailSyncService $service): void
    {
        if (!$this->account->is_active) {
            return;
        }

        $service->syncAccount($this->account);
    }
}
