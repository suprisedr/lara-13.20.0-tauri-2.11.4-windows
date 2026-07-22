<?php

namespace App\Services;

use App\Jobs\PostCreditNoteJob;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerCreditNotePostingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatch(int $companyId, int $userId, int $creditNoteId): void
    {
        $payload = json_encode(compact('companyId', 'userId', 'creditNoteId'));

        try {
            $queue = $this->jobs()->connect('credit_note_postings');
            $task  = $queue->create('post_credit_note', $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerCreditNotePostingDispatcher: RR unavailable, falling back to Laravel queue', [
                'credit_note_id' => $creditNoteId,
                'error'          => $e->getMessage(),
            ]);
            PostCreditNoteJob::dispatch($companyId, $userId, $creditNoteId);
        }
    }
}
