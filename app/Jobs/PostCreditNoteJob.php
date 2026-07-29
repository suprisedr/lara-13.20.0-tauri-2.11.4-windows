<?php

namespace App\Jobs;
use App\Events\PostingStatusUpdated;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\User;
use App\Services\CreditNotePostingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

class PostCreditNoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function backoff(): array
    {
        return [30, 90, 180];
    }

    public function __construct(
        public readonly int $companyId,
        public readonly int $userId,
        public readonly int $creditNoteId,
    ) {
        $this->onConnection('ai')->onQueue('credit-note-postings');
    }

    public function handle(CreditNotePostingService $service): void
    {
        $company    = Company::findOrFail($this->companyId);
        $user       = User::findOrFail($this->userId);
        $creditNote = CreditNote::findOrFail($this->creditNoteId);

        Log::info('PostCreditNoteJob: starting', ['credit_note_id' => $this->creditNoteId]);

        try {
            $service->post($company, $user, $creditNote);
            Log::info('PostCreditNoteJob: posted', ['credit_note_id' => $this->creditNoteId]);
        } catch (RateLimitedException | ProviderOverloadedException $e) {
            Log::warning('PostCreditNoteJob: transient AI failure, releasing', ['error' => $e->getMessage()]);
            $this->release(60);
        } catch (Throwable $e) {
            Log::error('PostCreditNoteJob: failed', ['credit_note_id' => $this->creditNoteId, 'error' => $e->getMessage()]);
            event(new PostingStatusUpdated(
                $this->companyId,
                'credit_note',
                $this->creditNoteId,
                'failed',
                'Credit note posting failed',
            ));
            throw $e;
        }
    }
}
