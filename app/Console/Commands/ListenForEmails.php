<?php

namespace App\Console\Commands;

use App\Models\CompanyEmailAccount;
use App\Services\EmailSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListenForEmails extends Command
{
    protected $signature = 'emails:listen
        {account? : The ID of the email account to listen on (omit to auto-detect the active account)}';

    protected $description = 'Poll for new emails at the interval configured per account.';

    public function handle(EmailSyncService $syncService): int
    {
        $account = $this->resolveAccount();

        if (!$account) {
            $this->info('No active email account found. Waiting...');
            $account = $this->waitForAccount();
        }

        $this->info("Watching {$account->email_address} (interval: {$account->sync_interval_minutes}min)...");

        while (true) {
            try {
                $count = $syncService->syncAccount($account);
                if ($count > 0) {
                    $this->info("Synced {$count} new email(s).");
                }
                $account->update(['last_error' => null]);
            } catch (\Throwable $e) {
                $account->update(['last_error' => $e->getMessage()]);
                Log::warning('Email poll error', ['account_id' => $account->id, 'error' => $e->getMessage()]);
                $this->warn("Poll error: {$e->getMessage()}");
            }

            $account->refresh();

            if (!$account->is_active) {
                $this->info('Account deactivated. Stopping.');
                return self::SUCCESS;
            }

            $interval = max(1, $account->sync_interval_minutes) * 60;
            sleep($interval);
        }
    }

    private function resolveAccount(): ?CompanyEmailAccount
    {
        if ($id = $this->argument('account')) {
            return CompanyEmailAccount::where('id', $id)->where('is_active', true)->first();
        }

        return CompanyEmailAccount::where('is_active', true)->first();
    }

    private function waitForAccount(): CompanyEmailAccount
    {
        while (true) {
            sleep(30);
            $account = CompanyEmailAccount::where('is_active', true)->first();
            if ($account) {
                $this->info("Found active account: {$account->email_address}");
                return $account;
            }
        }
    }
}
