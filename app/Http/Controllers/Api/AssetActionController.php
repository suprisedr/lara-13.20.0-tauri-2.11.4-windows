<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Services\RoadRunnerAssetPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetActionController extends Controller
{
    public function revalue(Asset $asset, Request $request): JsonResponse
    {
        $this->authorise($asset);

        $data = $request->validate([
            'new_carrying_amount' => ['required', 'numeric', 'gt:0'],
            'date'                => ['required', 'date'],
        ]);

        $event = AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_REVALUATION,
            'event_date'     => $data['date'],
            'amount'         => $data['new_carrying_amount'],
            'description'    => 'Revaluation to fair value R '.number_format((float) $data['new_carrying_amount'], 2),
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerAssetPostingDispatcher::class)->dispatch($event->id, $asset->id, auth()->id(), 'revalue', $data);

        return response()->json([
            'success' => true,
            'asset'   => $this->assetSummary($asset),
            'event'   => $this->eventSummary($event),
            'note'    => 'Revaluation recorded. AI will post the journal in the background.',
        ]);
    }

    public function impair(Asset $asset, Request $request): JsonResponse
    {
        $this->authorise($asset);

        $data = $request->validate([
            'impairment_amount' => ['required', 'numeric', 'gt:0'],
            'date'              => ['required', 'date'],
            'reason'            => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $desc = 'Impairment of R '.number_format((float) $data['impairment_amount'], 2);
        if (! empty($data['reason'])) {
            $desc .= ' — '.$data['reason'];
        }

        $event = AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_IMPAIRMENT,
            'event_date'     => $data['date'],
            'amount'         => $data['impairment_amount'],
            'description'    => $desc,
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerAssetPostingDispatcher::class)->dispatch($event->id, $asset->id, auth()->id(), 'impair', $data);

        return response()->json([
            'success' => true,
            'asset'   => $this->assetSummary($asset),
            'event'   => $this->eventSummary($event),
            'note'    => 'Impairment recorded. AI will post the journal in the background.',
        ]);
    }

    public function reverseImpairment(Asset $asset, Request $request): JsonResponse
    {
        $this->authorise($asset);

        $data = $request->validate([
            'reversal_amount' => ['required', 'numeric', 'gt:0'],
            'date'            => ['required', 'date'],
        ]);

        $event = AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_IMPAIRMENT_REVERSAL,
            'event_date'     => $data['date'],
            'amount'         => $data['reversal_amount'],
            'description'    => 'Impairment reversal of R '.number_format((float) $data['reversal_amount'], 2),
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerAssetPostingDispatcher::class)->dispatch($event->id, $asset->id, auth()->id(), 'reverse', $data);

        return response()->json([
            'success' => true,
            'asset'   => $this->assetSummary($asset),
            'event'   => $this->eventSummary($event),
            'note'    => 'Impairment reversal recorded. AI will post the journal in the background.',
        ]);
    }

    public function capitalise(Asset $asset, Request $request): JsonResponse
    {
        $this->authorise($asset);

        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'gt:0'],
            'date'        => ['required', 'date'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $desc = 'Subsequent cost capitalised: R '.number_format((float) $data['amount'], 2);
        if (! empty($data['description'])) {
            $desc .= ' — '.$data['description'];
        }

        $event = AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_CAPITALISATION,
            'event_date'     => $data['date'],
            'amount'         => $data['amount'],
            'description'    => $desc,
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerAssetPostingDispatcher::class)->dispatch($event->id, $asset->id, auth()->id(), 'capitalise', $data);

        return response()->json([
            'success' => true,
            'asset'   => $this->assetSummary($asset),
            'event'   => $this->eventSummary($event),
            'note'    => 'Subsequent cost recorded. AI will post the journal in the background.',
        ]);
    }

    private function authorise(Asset $asset): void
    {
        if ($asset->company->user_id !== auth()->id()) {
            abort(403);
        }
    }

    private function assetSummary(Asset $asset): array
    {
        return [
            'id'                     => $asset->id,
            'name'                   => $asset->name,
            'cost'                   => (float) $asset->cost,
            'accumulated_impairment' => (float) ($asset->accumulated_impairment ?? 0),
            'revaluation_surplus'    => (float) ($asset->revaluation_surplus ?? 0),
            'status'                 => $asset->status,
        ];
    }

    private function eventSummary(AssetEvent $event): array
    {
        return [
            'id'             => $event->id,
            'event_type'     => $event->event_type,
            'event_date'     => $event->event_date->format('Y-m-d'),
            'amount'         => (float) $event->amount,
            'journal_status' => $event->journal_status,
        ];
    }
}
