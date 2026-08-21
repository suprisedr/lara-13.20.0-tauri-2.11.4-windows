<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\RelatedParty;
use App\Models\RelatedPartyTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RelatedPartyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $parties = $company->relatedParties()
            ->withCount('relatedPartyTransactions')
            ->orderBy('name')
            ->get()
            ->map(fn (RelatedParty $p) => $this->partySummary($p));

        return response()->json($parties);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:150'],
            'relationship_type' => ['required', 'string', Rule::in(array_keys(RelatedParty::TYPES))],
            'description'       => ['nullable', 'string'],
            'contact_person'    => ['nullable', 'string', 'max:150'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $data['is_active'] ?? true;

        $party = $company->relatedParties()->create($data);

        return response()->json([
            'success' => true,
            'party'   => $this->partySummary($party),
            'note'    => 'Related party created.',
        ], 201);
    }

    public function show(RelatedParty $relatedParty): JsonResponse
    {
        $this->authorise($relatedParty);

        return response()->json($this->partySummary($relatedParty));
    }

    public function update(RelatedParty $relatedParty, Request $request): JsonResponse
    {
        $this->authorise($relatedParty);

        $data = $request->validate([
            'name'              => ['sometimes', 'string', 'max:150'],
            'relationship_type' => ['sometimes', 'string', Rule::in(array_keys(RelatedParty::TYPES))],
            'description'       => ['sometimes', 'nullable', 'string'],
            'contact_person'    => ['sometimes', 'nullable', 'string', 'max:150'],
            'is_active'         => ['sometimes', 'boolean'],
        ]);

        $relatedParty->update($data);

        return response()->json(['success' => true, 'party' => $this->partySummary($relatedParty->fresh())]);
    }

    public function transactions(RelatedParty $relatedParty): JsonResponse
    {
        $this->authorise($relatedParty);

        $txns = $relatedParty->relatedPartyTransactions()
            ->orderByDesc('transaction_date')
            ->get()
            ->map(fn (RelatedPartyTransaction $t) => $this->transactionSummary($t));

        return response()->json($txns);
    }

    public function storeTransaction(RelatedParty $relatedParty, Request $request): JsonResponse
    {
        $this->authorise($relatedParty);

        $data = $request->validate([
            'transaction_date'     => ['required', 'date'],
            'transaction_type'     => ['required', 'string', Rule::in(array_keys(RelatedPartyTransaction::TRANSACTION_TYPES))],
            'amount'               => ['required', 'numeric', 'min:0'],
            'description'          => ['nullable', 'string'],
            'outstanding_balance'  => ['nullable', 'numeric'],
            'terms_and_conditions' => ['nullable', 'string'],
            'is_arm_length'        => ['nullable', 'boolean'],
            'transaction_id'       => ['nullable', 'integer'],
        ]);

        $data['company_id']       = $relatedParty->company_id;
        $data['related_party_id'] = $relatedParty->id;
        $data['is_arm_length']    = $data['is_arm_length'] ?? true;

        $txn = RelatedPartyTransaction::create($data);

        return response()->json([
            'success'     => true,
            'transaction' => $this->transactionSummary($txn),
            'note'        => 'Related party transaction recorded.',
        ], 201);
    }

    private function company(Request $request): Company
    {
        $company = Company::findOrFail($request->input('company_id'));
        abort_unless($company->user_id === auth()->id(), 403);
        return $company;
    }

    private function authorise(RelatedParty $p): void
    {
        abort_unless($p->company->user_id === auth()->id(), 403);
    }

    private function partySummary(RelatedParty $p): array
    {
        return [
            'id'                 => $p->id,
            'name'               => $p->name,
            'relationship_type'  => $p->relationship_type,
            'relationship_label' => RelatedParty::TYPES[$p->relationship_type] ?? $p->relationship_type,
            'description'        => $p->description,
            'contact_person'     => $p->contact_person,
            'is_active'          => $p->is_active,
            'transactions_count' => $p->related_party_transactions_count ?? $p->relatedPartyTransactions()->count(),
        ];
    }

    private function transactionSummary(RelatedPartyTransaction $t): array
    {
        return [
            'id'                  => $t->id,
            'related_party_id'    => $t->related_party_id,
            'transaction_date'    => $t->transaction_date->format('Y-m-d'),
            'transaction_type'    => $t->transaction_type,
            'type_label'          => RelatedPartyTransaction::TRANSACTION_TYPES[$t->transaction_type] ?? $t->transaction_type,
            'amount'              => (float) $t->amount,
            'description'         => $t->description,
            'outstanding_balance' => $t->outstanding_balance !== null ? (float) $t->outstanding_balance : null,
            'terms_and_conditions' => $t->terms_and_conditions,
            'is_arm_length'       => $t->is_arm_length,
            'transaction_id'      => $t->transaction_id,
        ];
    }
}
