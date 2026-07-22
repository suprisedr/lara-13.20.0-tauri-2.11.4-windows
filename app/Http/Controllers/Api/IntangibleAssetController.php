<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RoadRunnerIntangiblePostingDispatcher;
use App\Models\IntangibleAsset;
use App\Models\IntangibleAssetEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntangibleAssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id'          => ['nullable', 'integer'],
            'status'              => ['nullable', 'in:active,disposed,all'],
            'intangible_class_id' => ['nullable', 'integer'],
            'per_page'            => ['nullable', 'integer', 'min:1', 'max:200'],
            'search'              => ['nullable', 'string', 'max:120'],
        ]);

        $status = $data['status'] ?? 'active';

        $query = IntangibleAsset::with(['company:id,registered_name', 'intangibleClass:id,name'])
            ->when($status === 'active',   fn ($q) => $q->active())
            ->when($status === 'disposed', fn ($q) => $q->disposed())
            ->when($data['company_id'] ?? null,          fn ($q, $id) => $q->where('company_id', $id))
            ->when($data['intangible_class_id'] ?? null, fn ($q, $id) => $q->where('intangible_class_id', $id))
            ->when($data['search'] ?? null, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                  ->orWhere('reference', 'like', "%{$s}%");
            }))
            ->orderByDesc('acquisition_date');

        $page = $query->paginate($data['per_page'] ?? 25);

        return response()->json([
            'data' => collect($page->items())->map(fn (IntangibleAsset $a) => $this->serialize($a)),
            'meta' => [
                'current_page'  => $page->currentPage(),
                'last_page'     => $page->lastPage(),
                'per_page'      => $page->perPage(),
                'total'         => $page->total(),
                'status_filter' => $status,
            ],
        ]);
    }

    public function show(IntangibleAsset $intangible): JsonResponse
    {
        $intangible->load([
            'company:id,registered_name',
            'intangibleClass:id,name,accounting_policy,indefinite_life',
            'postingTransaction',
            'disposalTransaction',
        ]);

        return response()->json(['data' => $this->serialize($intangible, withTransactions: true)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id'             => ['required', 'integer', 'exists:companies,id'],
            'intangible_class_id'    => ['nullable', 'integer', 'exists:intangible_classes,id'],
            'name'                   => ['required', 'string', 'max:160'],
            'reference'              => ['nullable', 'string', 'max:80'],
            'category'               => ['nullable', 'string', 'max:160'],
            'acquisition_date'       => ['required', 'date'],
            'cost'                   => ['required', 'numeric', 'min:0'],
            'residual_value'         => ['nullable', 'numeric', 'min:0'],
            'useful_life_years'      => ['nullable', 'numeric', 'min:0'],
            'useful_life_indefinite' => ['nullable', 'boolean'],
            'amortisation_method'    => ['nullable', 'in:straight_line,reducing_balance'],
            'notes'                  => ['nullable', 'string'],
        ]);

        $asset = IntangibleAsset::create(array_merge($data, [
            'residual_value' => $data['residual_value'] ?? 0,
            'status'         => IntangibleAsset::STATUS_ACTIVE,
        ]));

        $event = IntangibleAssetEvent::create([
            'intangible_asset_id' => $asset->id,
            'event_type'          => IntangibleAssetEvent::TYPE_ACQUISITION,
            'event_date'          => $data['acquisition_date'],
            'amount'              => $data['cost'],
            'description'         => 'Initial recognition at cost R '.number_format((float) $data['cost'], 2),
            'journal_status'      => IntangibleAssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerIntangiblePostingDispatcher::class)->dispatch($event->id, $asset->id, auth()->id(), 'acquire', $data);

        return response()->json([
            'data'  => $this->serialize($asset->fresh()),
            'event' => [
                'id'             => $event->id,
                'event_type'     => $event->event_type,
                'journal_status' => $event->journal_status,
            ],
            'note' => 'Intangible registered. AI will post the acquisition journal in the background.',
        ], 201);
    }

    public function update(Request $request, IntangibleAsset $intangible): JsonResponse
    {
        $data = $request->validate([
            'name'                   => ['nullable', 'string', 'max:160'],
            'reference'              => ['nullable', 'string', 'max:80'],
            'category'               => ['nullable', 'string', 'max:160'],
            'residual_value'         => ['nullable', 'numeric', 'min:0'],
            'useful_life_years'      => ['nullable', 'numeric', 'min:0'],
            'useful_life_indefinite' => ['nullable', 'boolean'],
            'amortisation_method'    => ['nullable', 'in:straight_line,reducing_balance'],
            'notes'                  => ['nullable', 'string'],
        ]);

        $intangible->update($data);

        return response()->json(['data' => $this->serialize($intangible->fresh())]);
    }

    public function dispose(Request $request, IntangibleAsset $intangible): JsonResponse
    {
        if ($intangible->status === IntangibleAsset::STATUS_DISPOSED) {
            return response()->json(['message' => 'Intangible already disposed.'], 422);
        }

        $data = $request->validate([
            'disposal_date'     => ['required', 'date'],
            'disposal_proceeds' => ['nullable', 'numeric', 'min:0'],
        ]);

        $intangible->update([
            'disposal_date'     => $data['disposal_date'],
            'disposal_proceeds' => $data['disposal_proceeds'] ?? 0,
        ]);

        $event = IntangibleAssetEvent::create([
            'intangible_asset_id' => $intangible->id,
            'event_type'          => IntangibleAssetEvent::TYPE_DISPOSAL,
            'event_date'          => $data['disposal_date'],
            'amount'              => $data['disposal_proceeds'] ?? 0,
            'description'         => 'Intangible disposed. Proceeds: R '.number_format((float) ($data['disposal_proceeds'] ?? 0), 2),
            'journal_status'      => IntangibleAssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerIntangiblePostingDispatcher::class)->dispatch($event->id, $intangible->id, auth()->id(), 'dispose', $data);

        return response()->json([
            'data'  => $this->serialize($intangible->fresh()),
            'event' => ['id' => $event->id, 'event_type' => $event->event_type, 'journal_status' => $event->journal_status],
            'note'  => 'Disposal recorded. AI will post the journal in the background.',
        ]);
    }

    private function serialize(IntangibleAsset $a, bool $withTransactions = false): array
    {
        $payload = [
            'id'      => $a->id,
            'company' => $a->company ? ['id' => $a->company->id, 'name' => $a->company->registered_name] : null,
            'intangible_class' => $a->intangibleClass ? [
                'id'                => $a->intangibleClass->id,
                'name'              => $a->intangibleClass->name,
                'accounting_policy' => $a->intangibleClass->accounting_policy,
                'indefinite_life'   => (bool) $a->intangibleClass->indefinite_life,
            ] : null,
            'name'                        => $a->name,
            'reference'                   => $a->reference,
            'category'                    => $a->category,
            'acquisition_date'            => optional($a->acquisition_date)->toDateString(),
            'cost'                        => (float) $a->cost,
            'residual_value'              => (float) $a->residual_value,
            'useful_life_years'           => $a->useful_life_years !== null ? (float) $a->useful_life_years : null,
            'useful_life_indefinite'      => (bool) $a->useful_life_indefinite,
            'amortisation_method'         => $a->effectiveAmortisationMethod(),
            'status'                      => $a->status,
            'disposal_date'               => optional($a->disposal_date)->toDateString(),
            'disposal_proceeds'           => $a->disposal_proceeds !== null ? (float) $a->disposal_proceeds : null,
            'accumulated_amortisation'    => round($a->accumulatedAmortisation(now()->toDateString()), 2),
            'accumulated_impairment'      => (float) ($a->accumulated_impairment ?? 0),
            'revaluation_surplus'         => (float) ($a->revaluation_surplus ?? 0),
            'carrying_value'              => $a->netBookValue(now()->toDateString()),
            'posted_at'                   => optional($a->posted_at)->toIso8601String(),
            'last_amortisation_posted_on' => optional($a->last_amortisation_posted_on)->toDateString(),
        ];

        if ($withTransactions) {
            $payload['posting_transaction_id']  = $a->posting_transaction_id;
            $payload['disposal_transaction_id'] = $a->disposal_transaction_id;
        }

        return $payload;
    }
}
