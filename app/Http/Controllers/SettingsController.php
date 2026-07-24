<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private const AI_KEYS = [
        'gemini_api_key' => [
            'label' => 'Gemini API Key',
            'env' => 'GEMINI_API_KEY',
            'placeholder' => 'AIza...',
            'help' => 'Required for all AI posting agents (invoices, assets, leases, inventory, etc.)',
            'required' => true,
        ],
        'openai_api_key' => [
            'label' => 'OpenAI API Key',
            'env' => 'OPENAI_API_KEY',
            'placeholder' => 'sk-...',
            'help' => 'Used for embeddings and default AI operations.',
        ],
        'anthropic_api_key' => [
            'label' => 'Anthropic API Key',
            'env' => 'ANTHROPIC_API_KEY',
            'placeholder' => 'sk-ant-...',
            'help' => 'Optional alternative AI provider.',
        ],
        'meilisearch_key' => [
            'label' => 'Meilisearch Master Key',
            'env' => 'MEILISEARCH_KEY',
            'placeholder' => 'master-key...',
            'help' => 'Leave blank for development (no authentication). Set for production security.',
        ],
    ];

    public function edit()
    {
        $settings = [];
        foreach (self::AI_KEYS as $key => $meta) {
            $value = AppSetting::get($key);
            $settings[$key] = [
                ...$meta,
                'has_value' => $value !== null && $value !== '',
            ];
        }

        return view('settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $updated = 0;

        foreach (self::AI_KEYS as $key => $meta) {
            $value = $request->input($key);

            if ($value === null) {
                continue;
            }

            if ($value === '') {
                AppSetting::where('key', $key)->delete();
            } else {
                AppSetting::set($key, $value);
            }

            $updated++;
        }

        if ($request->boolean('retry_failed_jobs')) {
            $this->retryFailedAiJobs();
        }

        return redirect()->route('settings.edit')
            ->with('status', $updated > 0 ? 'settings-updated' : 'no-changes');
    }

    private function retryFailedAiJobs(): void
    {
        $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')
            ->where('payload', 'like', '%PostInvoiceWithAi%')
            ->orWhere('payload', 'like', '%PostAssetAcquisitionWithAi%')
            ->orWhere('payload', 'like', '%PostAssetDisposalWithAi%')
            ->orWhere('payload', 'like', '%PostInvoicePaymentWithAi%')
            ->get();

        foreach ($failedJobs as $job) {
            \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => [$job->uuid]]);
        }
    }
}
