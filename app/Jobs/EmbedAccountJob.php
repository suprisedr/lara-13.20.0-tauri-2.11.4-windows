<?php

namespace App\Jobs;

use App\Models\AccountVector;
use App\Models\ChartOfAccount;
use App\Queue\Middleware\CircuitBreakerMiddleware;
use App\Services\EmbeddingRecovery;
use App\Services\EmbeddingSuspension;
use App\Services\GeminiEmbeddingService;
use App\Services\GeminiRateLimitedException;
use GabrielAnhaia\LaravelCircuitBreaker\Facades\CircuitBreaker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class EmbedAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const SERVICE = 'gemini-embedding';
    public const QUEUE   = 'embeddings';

    public int $tries = 3;

    public function backoff(): array
    {
        return [30, 90, 180];
    }

    public function __construct(public readonly int $accountId)
    {
        $this->onQueue(self::QUEUE);
    }

    public function middleware(): array
    {
        return [new CircuitBreakerMiddleware(self::SERVICE)];
    }

    public function handle(GeminiEmbeddingService $gemini): void
    {
        $account = ChartOfAccount::with('company')->find($this->accountId);
        if (! $account) {
            return;
        }

        try {
            $embedding = $gemini->embed($account->toEmbeddableText(), 'RETRIEVAL_DOCUMENT');
            CircuitBreaker::recordSuccess(self::SERVICE);
            EmbeddingRecovery::clear();
        } catch (GeminiRateLimitedException $e) {
            CircuitBreaker::recordFailure(self::SERVICE);
            EmbeddingSuspension::suspendFor($e->retryAfterSeconds);
            EmbeddingRecovery::set('account', $this->accountId);
            $this->release($e->retryAfterSeconds);
            return;
        } catch (Throwable $e) {
            CircuitBreaker::recordFailure(self::SERVICE);
            EmbeddingRecovery::set('account', $this->accountId);
            throw $e;
        }

        $vectorLiteral = AccountVector::formatVector($embedding);

        DB::connection('pgsql')->transaction(function () use ($account, $vectorLiteral) {
            $payload = [
                'mysql_account_id' => $account->id,
                'mysql_company_id' => $account->company_id,
                'account_code'     => $account->account_code,
                'account_name'     => $account->account_name,
                'account_type'     => $account->account_type,
                'category'         => $account->category,
                'updated_at'       => now(),
            ];

            $existing = AccountVector::where('mysql_account_id', $account->id)->first();
            if ($existing) {
                AccountVector::where('id', $existing->id)->update($payload);
                $id = $existing->id;
            } else {
                $payload['created_at'] = now();
                $id = DB::connection('pgsql')->table('account_vectors')->insertGetId($payload);
            }

            DB::connection('pgsql')->statement(
                'UPDATE account_vectors SET embedding = ?::vector WHERE id = ?',
                [$vectorLiteral, $id]
            );
        });

        $account->forceFill([
            'is_embedded' => true,
            'embedded_at' => now(),
        ])->save();
    }

    public function failed(Throwable $e): void
    {
        if (EmbeddingRecovery::isPivot('account', $this->accountId)) {
            EmbeddingRecovery::clear();
        }
    }

    public function bounce(int $delay): void
    {
        self::dispatch($this->accountId)
            ->onQueue(self::QUEUE)
            ->delay(now()->addSeconds(max($delay, 1)));

        $this->delete();
    }
}
