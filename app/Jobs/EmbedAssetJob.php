<?php

namespace App\Jobs;
use App\Models\Asset;
use App\Models\AssetVector;
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

class EmbedAssetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const SERVICE = 'gemini-embedding';
    public const QUEUE   = 'embeddings';

    public int $tries = 3;

    public function backoff(): array
    {
        return [30, 90, 180];
    }

    public function __construct(public readonly int $assetId)
    {
        $this->onConnection('ai')->onQueue(self::QUEUE);
    }

    public function middleware(): array
    {
        return [new CircuitBreakerMiddleware(self::SERVICE)];
    }

    public function handle(GeminiEmbeddingService $gemini): void
    {
        $asset = Asset::with(['ppeClass', 'company'])->find($this->assetId);
        if (! $asset || $asset->is_embedded) {
            return;
        }

        try {
            $embedding = $gemini->embed($asset->toEmbeddableText(), 'RETRIEVAL_DOCUMENT');
            CircuitBreaker::recordSuccess(self::SERVICE);
            EmbeddingRecovery::clear();
        } catch (GeminiRateLimitedException $e) {
            CircuitBreaker::recordFailure(self::SERVICE);
            EmbeddingSuspension::suspendFor($e->retryAfterSeconds);
            EmbeddingRecovery::set('asset', $this->assetId);
            $this->release($e->retryAfterSeconds);
            return;
        } catch (Throwable $e) {
            CircuitBreaker::recordFailure(self::SERVICE);
            EmbeddingRecovery::set('asset', $this->assetId);
            throw $e;
        }

        $vectorLiteral = AssetVector::formatVector($embedding);

        DB::connection('pgsql')->transaction(function () use ($asset, $vectorLiteral) {
            $payload = [
                'mysql_asset_id'   => $asset->id,
                'cost'             => $asset->cost,
                'currency'         => $asset->company?->currency ?? 'ZAR',
                'asset_name'       => $asset->name,
                'asset_class'      => $asset->ppeClass?->name,
                'accounting_policy' => $asset->ppeClass?->accounting_policy ?? 'cost',
                'acquisition_date' => optional($asset->acquisition_date)->format('Y-m-d'),
                'updated_at'       => now(),
            ];

            $existing = AssetVector::where('mysql_asset_id', $asset->id)->first();
            if ($existing) {
                AssetVector::where('id', $existing->id)->update($payload);
                $id = $existing->id;
            } else {
                $payload['created_at'] = now();
                $id = DB::connection('pgsql')->table('asset_vectors')->insertGetId($payload);
            }

            DB::connection('pgsql')->statement(
                'UPDATE asset_vectors SET embedding = ?::vector WHERE id = ?',
                [$vectorLiteral, $id]
            );
        });

        $asset->forceFill([
            'is_embedded' => true,
            'embedded_at' => now(),
        ])->save();
    }

    public function failed(Throwable $e): void
    {
        if (EmbeddingRecovery::isPivot('asset', $this->assetId)) {
            EmbeddingRecovery::clear();
        }
    }

    public function bounce(int $delay): void
    {
        self::dispatch($this->assetId)
            ->onQueue(self::QUEUE)
            ->delay(now()->addSeconds(max($delay, 1)));

        $this->delete();
    }
}
