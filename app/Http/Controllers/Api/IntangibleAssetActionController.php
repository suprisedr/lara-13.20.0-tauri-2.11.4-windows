<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RoadRunnerIntangiblePostingDispatcher;
use App\Models\IntangibleAsset;
use App\Models\IntangibleAssetEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntangibleAssetActionController extends Controller
{
    public function revalue(IntangibleAsset $intangible, Request $request): JsonResponse
    {
        $this->authorise($intangible);

        $data = $request->validate([
            'new_carrying_amount' => ['required', 'numeric', 'gt:0'],
            'date'                => ['required', 'date'],
        ]);

        $event = $this->record($intangible, IntangibleAssetEvent::TYPE_REVALUATION, $data['date'], $data['new_carrying_amount'],
            'Revaluation to fair value R '.number_format((float) $data['new_carrying_amount'], 2));

        app(RoadRunnerIntangiblePostingDispatcher::class)->dispatch($event->id, $intangible->id, auth()->id(), 'revalue', $data);

        return $this->response($intangible, $event, 'Revaluation recorded.');
    }

    public function impair(IntangibleAsset $intangible, Request $request): JsonResponse
    {
        $this->authorise($intangible);

        $data = $request->validate([
            'impairment_amount' => ['required', 'numeric', 'gt:0'],
            'date'              => ['required', 'date'],
            'reason'            => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $desc = 'Impairment of R '.number_format((float) $data['impairment_amount'], 2);
        if (! empty($data['reason'])) {
            $desc .= ' — '.$data['reason'];
        }

        $event = $this->record($intangible, IntangibleAssetEvent::TYPE_IMPAIRMENT, $data['date'], $data['impairment_amount'], $desc);

        app(RoadRunnerIntangiblePostingDispatcher::class)->dispatch($event->id, $intangible->id, auth()->id(), 'impair', $data);

        return $this->response($intangible, $event, 'Impairment recorded.');
    }

    public function reverseImpairment(IntangibleAsset $intangible, Request $request): JsonResponse
    {
        $this->authorise($intangible);

        $data = $request->validate([
            'reversal_amount' => ['required', 'numeric', 'gt:0'],
            'date'            => ['required', 'date'],
        ]);

        $event = $this->record($intangible, IntangibleAssetEvent::TYPE_IMPAIRMENT_REVERSAL, $data['date'], $data['reversal_amount'],
            'Impairment reversal of R '.number_format((float) $data['reversal_amount'], 2));

        app(RoadRunnerIntangiblePostingDispatcher::class)->dispatch($event->id, $intangible->id, auth()->id(), 'reverse', $data);

        return $this->response($intangible, $event, 'Impairment reversal recorded.');
    }

    public function capitalise(IntangibleAsset $intangible, Request $request): JsonResponse
    {
        $this->authorise($intangible);

        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'gt:0'],
            'date'        => ['required', 'date'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $desc = 'Subsequent cost capitalised: R '.number_format((float) $data['amount'], 2);
        if (! empty($data['description'])) {
            $desc .= ' — '.$data['description'];
        }

        $event = $this->record($intangible, IntangibleAssetEvent::TYPE_CAPITALISATION, $data['date'], $data['amount'], $desc);

        app(RoadRunnerIntangiblePostingDispatcher::class)->dispatch($event->id, $intangible->id, auth()->id(), 'capitalise', $data);

        return $this->response($intangible, $event, 'Subsequent cost recorded.');
    }

    private function authorise(IntangibleAsset $a): void
    {
        if ($a->company->user_id !== auth()->id()) {
            abort(403);
        }
    }

    private function record(IntangibleAsset $a, string $type, string $date, float $amount, string $desc): IntangibleAssetEvent
    {
        return IntangibleAssetEvent::create([
            'intangible_asset_id' => $a->id,
            'event_type'          => $type,
            'event_date'          => $date,
            'amount'              => $amount,
            'description'         => $desc,
            'journal_status'      => IntangibleAssetEvent::STATUS_PENDING,
        ]);
    }

    private function response(IntangibleAsset $a, IntangibleAssetEvent $e, string $msg): JsonResponse
    {
        return response()->json([
            'success' => true,
            'asset'   => [
                'id'                     => $a->id,
                'name'                   => $a->name,
                'cost'                   => (float) $a->cost,
                'accumulated_impairment' => (float) ($a->accumulated_impairment ?? 0),
                'revaluation_surplus'    => (float) ($a->revaluation_surplus ?? 0),
                'status'                 => $a->status,
            ],
            'event' => [
                'id'             => $e->id,
                'event_type'     => $e->event_type,
                'event_date'     => $e->event_date->format('Y-m-d'),
                'amount'         => (float) $e->amount,
                'journal_status' => $e->journal_status,
            ],
            'note' => $msg.' AI will post the journal in the background.',
        ]);
    }
}
