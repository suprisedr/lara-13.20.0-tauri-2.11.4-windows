<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,disposed,all'],
            'ppe_class_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $status = $data['status'] ?? 'active';

        $query = Asset::with(['company:id,registered_name', 'ppeClass:id,name'])
            ->when($status === 'active', fn ($q) => $q->active())
            ->when($status === 'disposed', fn ($q) => $q->disposed())
            ->when($data['company_id'] ?? null, fn ($q, $id) => $q->where('company_id', $id))
            ->when($data['ppe_class_id'] ?? null, fn ($q, $id) => $q->where('ppe_class_id', $id))
            ->when($data['search'] ?? null, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                  ->orWhere('asset_tag', 'like', "%{$s}%");
            }))
            ->orderByDesc('acquisition_date');

        $page = $query->paginate($data['per_page'] ?? 25);

        return response()->json([
            'data' => collect($page->items())->map(fn (Asset $a) => $this->serialize($a)),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'status_filter' => $status,
            ],
        ]);
    }

    public function show(Asset $asset): JsonResponse
    {
        $asset->load(['company:id,registered_name', 'ppeClass:id,name', 'postingTransaction', 'disposalTransaction']);

        return response()->json(['data' => $this->serialize($asset, withTransactions: true)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'ppe_class_id' => ['required', 'integer', 'exists:ppe_classes,id'],
            'name' => ['required', 'string', 'max:160'],
            'asset_tag' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:160'],
            'acquisition_date' => ['required', 'date'],
            'cost' => ['required', 'numeric', 'min:0'],
            'residual_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_years' => ['nullable', 'numeric', 'min:0'],
            'sars_wear_tear_years' => ['nullable', 'numeric', 'min:0'],
            'depreciation_method' => ['nullable', 'in:straight_line,reducing_balance'],
            'notes' => ['nullable', 'string'],
            'disposal_date' => ['nullable', 'date'],
            'disposal_proceeds' => ['nullable', 'numeric', 'min:0'],
        ]);

        $asset = Asset::create(array_merge($data, [
            'residual_value' => $data['residual_value'] ?? 0,
            'status' => Asset::STATUS_ACTIVE,
        ]));

        // AssetObserver::created() fires AssetCreated → PostAssetAcquisitionWithAi.
        // No separate dispatch needed here.
        $event = AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_ACQUISITION,
            'event_date'     => $data['acquisition_date'],
            'amount'         => $data['cost'],
            'description'    => 'Initial recognition at cost R '.number_format((float) $data['cost'], 2),
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        return response()->json([
            'data' => $this->serialize($asset->fresh()),
            'event' => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'journal_status' => $event->journal_status,
            ],
            'note' => 'Asset registered. AI will post the acquisition journal in the background.',
        ], 201);
    }

    public function update(Request $request, Asset $asset): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:160'],
            'asset_tag' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:160'],
            'residual_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_years' => ['nullable', 'numeric', 'min:0'],
            'sars_wear_tear_years' => ['nullable', 'numeric', 'min:0'],
            'depreciation_method' => ['nullable', 'in:straight_line,reducing_balance'],
            'notes' => ['nullable', 'string'],
        ]);

        $asset->update($data);

        return response()->json(['data' => $this->serialize($asset->fresh())]);
    }

    /**
     * Mark an asset as disposed. Setting disposal_date triggers the observer
     * which flips status → disposed and dispatches AssetDisposed → posting.
     */
    public function dispose(Request $request, Asset $asset): JsonResponse
    {
        if ($asset->status === Asset::STATUS_DISPOSED) {
            return response()->json(['message' => 'Asset already disposed.'], 422);
        }

        $data = $request->validate([
            'disposal_date' => ['required', 'date'],
            'disposal_proceeds' => ['nullable', 'numeric', 'min:0'],
        ]);

        $asset->update([
            'disposal_date' => $data['disposal_date'],
            'disposal_proceeds' => $data['disposal_proceeds'] ?? 0,
        ]);

        // AssetObserver::updated() fires AssetDisposed → PostAssetDisposalWithAi.
        // No separate dispatch needed here.
        $event = AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_DISPOSAL,
            'event_date'     => $data['disposal_date'],
            'amount'         => $data['disposal_proceeds'] ?? 0,
            'description'    => 'Asset disposed. Proceeds: R '.number_format((float) ($data['disposal_proceeds'] ?? 0), 2),
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        return response()->json([
            'data' => $this->serialize($asset->fresh()),
            'event' => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'journal_status' => $event->journal_status,
            ],
            'note' => 'Disposal recorded. AI will post the journal in the background.',
        ]);
    }

    /**
     * Read-only depreciation schedule for one asset.
     */
    public function depreciationSchedule(Asset $asset): JsonResponse
    {
        $start = $asset->acquisition_date?->startOfMonth();
        $life = $asset->effectiveUsefulLifeYears();
        if (! $start || ! $life) {
            return response()->json(['data' => []]);
        }

        $depreciable = (float) $asset->cost - (float) $asset->residual_value;
        if ($depreciable <= 0) {
            return response()->json(['data' => []]);
        }

        $months = (int) round($life * 12);
        $monthly = round($depreciable / $months, 2);
        $rows = [];
        $accum = 0.0;
        for ($i = 0; $i < $months; $i++) {
            $monthEnd = $start->copy()->addMonths($i)->endOfMonth();
            $accum = round($accum + $monthly, 2);
            $rows[] = [
                'period' => $monthEnd->format('Y-m'),
                'depreciation' => $monthly,
                'accumulated' => $accum,
                'carrying_value' => round((float) $asset->cost - $accum, 2),
                'posted' => $asset->last_depreciation_posted_on
                    && $asset->last_depreciation_posted_on->greaterThanOrEqualTo($monthEnd),
            ];
        }

        return response()->json(['data' => $rows]);
    }

    private function serialize(Asset $asset, bool $withTransactions = false): array
    {
        $payload = [
            'id' => $asset->id,
            'company' => $asset->company ? [
                'id' => $asset->company->id,
                'name' => $asset->company->registered_name,
            ] : null,
            'ppe_class' => $asset->ppeClass ? [
                'id' => $asset->ppeClass->id,
                'name' => $asset->ppeClass->name,
            ] : null,
            'name' => $asset->name,
            'asset_tag' => $asset->asset_tag,
            'location' => $asset->location,
            'acquisition_date' => optional($asset->acquisition_date)->toDateString(),
            'cost' => (float) $asset->cost,
            'residual_value' => (float) $asset->residual_value,
            'useful_life_years' => (float) $asset->useful_life_years,
            'sars_wear_tear_years' => $asset->sars_wear_tear_years !== null ? (float) $asset->sars_wear_tear_years : null,
            'depreciation_method' => $asset->effectiveDepreciationMethod(),
            'status' => $asset->status,
            'disposal_date' => optional($asset->disposal_date)->toDateString(),
            'disposal_proceeds' => $asset->disposal_proceeds !== null ? (float) $asset->disposal_proceeds : null,
            'posted_at' => optional($asset->posted_at)->toIso8601String(),
            'last_depreciation_posted_on' => optional($asset->last_depreciation_posted_on)->toDateString(),
            'accumulated_depreciation' => round($asset->accumulatedDepreciation(now()->toDateString()), 2),
            'carrying_value' => round((float) $asset->cost - $asset->accumulatedDepreciation(now()->toDateString()), 2),
        ];

        if ($withTransactions) {
            $payload['posting_transaction_id'] = $asset->posting_transaction_id;
            $payload['disposal_transaction_id'] = $asset->disposal_transaction_id;
        }

        return $payload;
    }
}
