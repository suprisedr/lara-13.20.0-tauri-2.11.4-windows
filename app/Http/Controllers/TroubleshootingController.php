<?php

namespace App\Http\Controllers;

use GabrielAnhaia\LaravelCircuitBreaker\Facades\CircuitBreaker;
use GabrielAnhaia\PhpCircuitBreaker\CircuitState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class TroubleshootingController extends Controller
{
    private const CIRCUIT_SERVICES = [
        'invoice-ai-posting' => 'Invoice Posting',
        'invoice-payment-ai-posting' => 'Invoice Payment Posting',
        'asset-ai-posting' => 'Asset Posting',
        'lease-ai-posting' => 'Lease Posting',
        'intangible-ai-posting' => 'Intangible Asset Posting',
        'inventory-ai-posting' => 'Inventory Posting',
        'investment-property-ai-posting' => 'Investment Property Posting',
        'biological_asset_posting' => 'Biological Asset Posting',
        'gemini-embedding' => 'Gemini Embeddings',
    ];

    public function index(Request $request)
    {
        return view('troubleshooting.index', [
            'failedJobs' => $this->getFailedJobs(),
            'queuedJobs' => $this->getQueuedJobs(),
            'circuitStates' => $this->getCircuitStates(),
            'logLines' => $this->recentLogEntries(),
        ]);
    }

    public function feed(): JsonResponse
    {
        return response()->json([
            'failedJobs' => $this->getFailedJobs(),
            'queuedJobs' => $this->getQueuedJobs(),
            'circuitStates' => $this->getCircuitStates(),
            'logLines' => $this->recentLogEntries(),
            'timestamp' => now()->format('H:i:s'),
        ]);
    }

    public function logsFeed(): JsonResponse
    {
        return response()->json([
            'lines' => $this->recentLogEntries(),
        ]);
    }

    public function retryJob(string $id): JsonResponse
    {
        $job = DB::table('failed_jobs')->find($id);

        if (! $job) {
            return response()->json(['ok' => false, 'message' => 'Job not found'], 404);
        }

        Artisan::call('queue:retry', ['id' => [$job->uuid]]);

        return response()->json(['ok' => true, 'message' => 'Job re-queued']);
    }

    public function retryAll(): JsonResponse
    {
        $count = DB::table('failed_jobs')->count();
        Artisan::call('queue:retry', ['id' => ['all']]);

        return response()->json(['ok' => true, 'message' => $count.' jobs re-queued']);
    }

    public function deleteJob(string $id): JsonResponse
    {
        $deleted = DB::table('failed_jobs')->where('id', $id)->delete();

        if (! $deleted) {
            return response()->json(['ok' => false, 'message' => 'Job not found'], 404);
        }

        return response()->json(['ok' => true, 'message' => 'Job removed']);
    }

    private function getFailedJobs()
    {
        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(50)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                $displayName = $payload['displayName'] ?? $payload['data']['commandName'] ?? 'Unknown Job';

                return [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'queue' => $job->queue,
                    'display_name' => class_basename($displayName),
                    'exception_summary' => strtok($job->exception, "\n"),
                    'exception_full' => mb_substr($job->exception, 0, 2000),
                    'failed_at' => $job->failed_at,
                ];
            });
    }

    private function getQueuedJobs()
    {
        return DB::table('jobs')
            ->selectRaw('queue, count(*) as total, max(attempts) as max_attempts')
            ->groupBy('queue')
            ->orderBy('queue')
            ->get();
    }

    private function getCircuitStates(): array
    {
        $states = [];
        foreach (self::CIRCUIT_SERVICES as $service => $label) {
            try {
                $state = CircuitBreaker::getState($service);
                $states[$service] = [
                    'label' => $label,
                    'state' => $state instanceof CircuitState ? $state->value : (string) $state,
                ];
            } catch (\Throwable) {
                $states[$service] = ['label' => $label, 'state' => 'closed'];
            }
        }

        return $states;
    }

    /**
     * @return array<int, array{date: string, level: string, message: string}>
     */
    private function recentLogEntries(int $limit = 60): array
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            return [];
        }

        $size = filesize($path);
        $chunk = min($size, 300_000);
        $handle = fopen($path, 'r');
        fseek($handle, -$chunk, SEEK_END);
        $tail = fread($handle, $chunk);
        fclose($handle);

        preg_match_all(
            '/^\[(?<date>[\d\-: ]+)\]\s+\w+\.(?<level>\w+):\s+(?<message>.*)$/m',
            $tail,
            $matches,
            PREG_SET_ORDER
        );

        $entries = array_map(fn ($m) => [
            'date' => $m['date'],
            'level' => strtoupper($m['level']),
            'message' => mb_substr($m['message'], 0, 500),
        ], $matches);

        return array_slice(array_reverse($entries), 0, $limit);
    }
}
