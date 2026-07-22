<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JournalLine;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    /**
     * PATCH /api/transactions/{transaction}
     *
     * Accepts any combination of:
     *   description, transaction_date, notes
     *   lines[].id + (chart_of_account_id | amount | description)
     *
     * Only draft transactions allow account/amount line edits.
     * All transactions allow description, date, notes edits (unless immutable).
     */
    public function update(Transaction $transaction, Request $request): JsonResponse
    {
        $company = $transaction->company;

        if ($company->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'description'      => ['sometimes', 'string', 'max:255'],
            'transaction_date' => ['sometimes', 'date'],
            'notes'            => ['sometimes', 'nullable', 'string', 'max:5000'],
            'lines'            => ['sometimes', 'array'],
            'lines.*.id'       => ['required_with:lines', 'integer'],
            'lines.*.chart_of_account_id' => ['sometimes', 'integer'],
            'lines.*.amount'   => ['sometimes', 'numeric', 'gt:0'],
            'lines.*.description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'new_lines'            => ['sometimes', 'array'],
            'new_lines.*.chart_of_account_id' => ['required_with:new_lines', 'integer'],
            'new_lines.*.type'   => ['required_with:new_lines', 'in:debit,credit'],
            'new_lines.*.amount' => ['required_with:new_lines', 'numeric', 'gt:0'],
            'new_lines.*.description' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        if ($transaction->isImmutable() && (isset($validated['transaction_date']) || isset($validated['lines']))) {
            return response()->json(['message' => 'Reversed transactions and reversal entries cannot have their date or lines edited.'], 422);
        }

        $changes = [];

        if (isset($validated['description'])) {
            $transaction->update(['description' => $validated['description']]);
            $changes[] = 'description';
        }

        if (isset($validated['transaction_date'])) {
            $transaction->update(['transaction_date' => $validated['transaction_date']]);
            $changes[] = 'transaction_date';
        }

        if (array_key_exists('notes', $validated)) {
            $transaction->update(['notes' => $validated['notes']]);
            $changes[] = 'notes';
        }

        if (! empty($validated['lines'])) {
            foreach ($validated['lines'] as $lineData) {
                $line = JournalLine::find($lineData['id']);

                if (! $line || $line->transaction_id !== $transaction->id) {
                    return response()->json(['message' => "Line #{$lineData['id']} does not belong to this transaction."], 422);
                }

                if (isset($lineData['chart_of_account_id']) || isset($lineData['amount'])) {
                    if ($transaction->status !== 'draft') {
                        return response()->json(['message' => 'Account and amount changes are only allowed on draft transactions.'], 422);
                    }
                }

                if (isset($lineData['chart_of_account_id'])) {
                    $account = $company->chartOfAccounts()->postable()->find($lineData['chart_of_account_id']);
                    if (! $account) {
                        return response()->json(['message' => "Account #{$lineData['chart_of_account_id']} not found or not postable."], 422);
                    }
                    $line->update(['chart_of_account_id' => $account->id]);
                }

                if (isset($lineData['amount'])) {
                    $line->update(['amount' => round((float) $lineData['amount'], 2)]);
                }

                if (array_key_exists('description', $lineData)) {
                    $line->update(['description' => $lineData['description']]);
                }
            }
            $changes[] = 'lines';
        }

        if (! empty($validated['new_lines'])) {
            if ($transaction->isImmutable()) {
                return response()->json(['message' => 'Reversed transactions and reversal entries cannot have lines added.'], 422);
            }
            if ($transaction->status !== 'draft') {
                return response()->json(['message' => 'Lines can only be added to draft transactions.'], 422);
            }
            foreach ($validated['new_lines'] as $lineData) {
                $account = $company->chartOfAccounts()->postable()->find($lineData['chart_of_account_id']);
                if (! $account) {
                    return response()->json(['message' => "Account #{$lineData['chart_of_account_id']} not found or not postable."], 422);
                }
                $transaction->journalLines()->create([
                    'chart_of_account_id' => $account->id,
                    'type'                => $lineData['type'],
                    'amount'              => round((float) $lineData['amount'], 2),
                    'description'         => $lineData['description'] ?? null,
                ]);
            }
            $changes[] = 'new_lines';
        }

        $transaction->refresh()->load('journalLines.account');

        $debits  = (float) $transaction->journalLines->where('type', 'debit')->sum('amount');
        $credits = (float) $transaction->journalLines->where('type', 'credit')->sum('amount');

        return response()->json([
            'success'    => true,
            'changed'    => $changes,
            'balanced'   => abs($debits - $credits) < 0.01,
            'debit_total'  => round($debits, 2),
            'credit_total' => round($credits, 2),
            'transaction' => [
                'id'               => $transaction->id,
                'description'      => $transaction->description,
                'transaction_date' => optional($transaction->transaction_date)->format('Y-m-d'),
                'status'           => $transaction->status,
                'notes'            => $transaction->notes,
                'lines'            => $transaction->journalLines->map(fn ($l) => [
                    'id'                   => $l->id,
                    'type'                 => $l->type,
                    'amount'               => (float) $l->amount,
                    'description'          => $l->description,
                    'chart_of_account_id'  => $l->chart_of_account_id,
                    'account_code'         => $l->account?->account_code,
                    'account_name'         => $l->account?->account_name,
                ]),
            ],
        ]);
    }
}
