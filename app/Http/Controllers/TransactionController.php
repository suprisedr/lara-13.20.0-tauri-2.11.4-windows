<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionController extends Controller
{
    /**
     * Record a new manual double-entry transaction.
     */
    public function store(Company $company, Request $request, TransactionService $transactions): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'transaction_date'           => ['required', 'date'],
            'description'                => ['required', 'string', 'max:255'],
            'reference'                  => ['nullable', 'string', 'max:255'],
            'notes'                      => ['nullable', 'string', 'max:5000'],
            'source_document'            => ['nullable', 'string', 'max:10000'],
            'status'                     => ['required', 'in:draft,posted'],
            'lines'                      => ['required', 'array', 'min:2'],
            'lines.*.chart_of_account_id' => ['required', 'integer'],
            'lines.*.type'               => ['required', 'in:debit,credit'],
            'lines.*.amount'             => ['required', 'numeric', 'gt:0'],
            'lines.*.description'        => ['nullable', 'string', 'max:255'],
        ], [
            'lines.min'        => 'A transaction needs at least two journal lines.',
            'lines.*.amount.gt' => 'Each line amount must be greater than zero.',
        ]);

        try {
            $transaction = $transactions->record($company, $request->user(), $validated);
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['transaction' => $e->getMessage()]);
        }

        return redirect()
            ->route('companies.transactions', $company)
            ->with('success', "Transaction #{$transaction->id} recorded.");
    }

    public function updateStatus(Company $company, Transaction $transaction, Request $request): JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($transaction->company_id === $company->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,posted'],
        ]);

        if ($transaction->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft transactions can have their status changed.',
            ], 422);
        }

        $transaction->update(['status' => $validated['status']]);

        return response()->json(['success' => true, 'status' => $transaction->status]);
    }

    public function reverse(Company $company, Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($transaction->company_id === $company->id, 403);

        if ($transaction->status !== 'posted') {
            return back()->with('error', 'Only posted transactions can be reversed.');
        }

        if ($transaction->isReversed()) {
            return back()->with('error', 'This transaction has already been reversed.');
        }

        if ($transaction->isReversal()) {
            return back()->with('error', 'A reversal entry cannot itself be reversed.');
        }

        $transactions->reverse($transaction, auth()->user());

        return back()->with('success', 'Transaction reversed. A new reversal entry has been posted.');
    }

    public function createCorrection(Company $company, Transaction $transaction): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($transaction->company_id === $company->id, 403);

        if ($transaction->status !== 'reversed') {
            return back()->with('error', 'Only reversed transactions can have a correction created.');
        }

        $transaction->load(['correction', 'journalLines']);

        if ($transaction->correction !== null) {
            return back()->with('error', 'A correction draft already exists for this transaction.');
        }

        DB::transaction(function () use ($company, $transaction) {
            $correction = $company->transactions()->create([
                'user_id'          => auth()->id(),
                'transaction_date' => $transaction->transaction_date,
                'description'      => 'Correction: ' . $transaction->description,
                'reference'        => $transaction->reference,
                'status'           => 'draft',
                'notes'            => 'Correction of transaction #' . $transaction->id,
                'source_document'  => $transaction->source_document,
                'corrects_id'      => $transaction->id,
            ]);

            $correction->journalLines()->createMany(
                $transaction->journalLines->map(fn($line) => [
                    'chart_of_account_id' => $line->chart_of_account_id,
                    'customer_id'         => $line->customer_id,
                    'supplier_id'         => $line->supplier_id,
                    'type'                => $line->type,
                    'amount'              => $line->amount,
                    'description'         => $line->description,
                    'is_vat_line'         => $line->is_vat_line,
                    'vat_rate'            => $line->vat_rate,
                ])->all()
            );

            $correction->load('journalLines.account');
            $correction->searchable();
        });

        return back()->with('success', 'Correction draft created. Edit the lines and post when ready.');
    }

    public function updateNotes(Company $company, Transaction $transaction, Request $request): JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($transaction->company_id === $company->id, 403);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $transaction->update(['notes' => $validated['notes'] ?? null]);

        return response()->json(['success' => true]);
    }

    public function updateDescription(Company $company, Transaction $transaction, Request $request): JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($transaction->company_id === $company->id, 403);

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
        ]);

        $transaction->update(['description' => $validated['description']]);

        return response()->json(['success' => true, 'description' => $transaction->description]);
    }

    public function updateLineAccount(Company $company, Transaction $transaction, JournalLine $line, Request $request): JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($transaction->company_id === $company->id, 403);
        abort_unless($line->transaction_id === $transaction->id, 403);

        if ($transaction->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Account can only be changed on draft transactions.'], 422);
        }

        $validated = $request->validate([
            'chart_of_account_id' => ['required', 'integer'],
        ]);

        $account = $company->chartOfAccounts()->postable()->find($validated['chart_of_account_id']);
        if (! $account) {
            return response()->json(['success' => false, 'message' => 'Account not found or is a parent account that cannot be posted to.'], 422);
        }

        $line->update(['chart_of_account_id' => $account->id]);

        return response()->json([
            'success'      => true,
            'account_code' => $account->account_code,
            'account_name' => $account->account_name,
        ]);
    }

    public function updateLineNote(Company $company, Transaction $transaction, JournalLine $line, Request $request): JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($transaction->company_id === $company->id, 403);
        abort_unless($line->transaction_id === $transaction->id, 403);

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $line->update(['description' => $validated['description'] ?? null]);

        return response()->json(['success' => true]);
    }

    public function updateDate(Company $company, Transaction $transaction, Request $request): JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($transaction->company_id === $company->id, 403);

        if ($transaction->isImmutable()) {
            return response()->json(['success' => false, 'message' => 'Reversed transactions and reversal entries cannot be edited.'], 422);
        }

        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
        ]);

        $transaction->update(['transaction_date' => $validated['transaction_date']]);

        return response()->json([
            'success' => true,
            'date'    => $transaction->transaction_date->format('d M Y'),
        ]);
    }

    public function updateLineAmount(Company $company, Transaction $transaction, JournalLine $line, Request $request): JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($transaction->company_id === $company->id, 403);
        abort_unless($line->transaction_id === $transaction->id, 403);

        if ($transaction->isImmutable()) {
            return response()->json(['success' => false, 'message' => 'Reversed transactions and reversal entries cannot be edited.'], 422);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
        ], [
            'amount.gt' => 'The amount must be greater than zero.',
        ]);

        $line->update(['amount' => $validated['amount']]);

        return response()->json(array_merge(
            ['success' => true, 'amount' => number_format((float) $line->amount, 2)],
            $this->balancePayload($transaction),
        ));
    }

    public function storeLine(Company $company, Transaction $transaction, Request $request): JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($transaction->company_id === $company->id, 403);

        if ($transaction->isImmutable()) {
            return response()->json(['success' => false, 'message' => 'Reversed transactions and reversal entries cannot be edited.'], 422);
        }

        $validated = $request->validate([
            'chart_of_account_id' => ['required', 'integer'],
            'type'                => ['required', 'in:debit,credit'],
            'amount'              => ['required', 'numeric', 'gt:0'],
            'description'         => ['nullable', 'string', 'max:500'],
        ], [
            'amount.gt' => 'The amount must be greater than zero.',
        ]);

        $account = $company->chartOfAccounts()->postable()->find($validated['chart_of_account_id']);
        if (! $account) {
            return response()->json(['success' => false, 'message' => 'Account not found or is a parent account that cannot be posted to.'], 422);
        }

        $transaction->journalLines()->create([
            'chart_of_account_id' => $account->id,
            'type'                => $validated['type'],
            'amount'              => $validated['amount'],
            'description'         => $validated['description'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroyLine(Company $company, Transaction $transaction, JournalLine $line): JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($transaction->company_id === $company->id, 403);
        abort_unless($line->transaction_id === $transaction->id, 403);

        if ($transaction->isImmutable()) {
            return response()->json(['success' => false, 'message' => 'Reversed transactions and reversal entries cannot be edited.'], 422);
        }

        $line->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Recompute debit/credit totals for a transaction so the UI can reflect
     * the live balance after an inline edit.
     */
    private function balancePayload(Transaction $transaction): array
    {
        $transaction->load('journalLines');
        $debits = (float) $transaction->journalLines->where('type', 'debit')->sum('amount');
        $credits = (float) $transaction->journalLines->where('type', 'credit')->sum('amount');

        return [
            'debits'   => number_format($debits, 2),
            'credits'  => number_format($credits, 2),
            'balanced' => abs($debits - $credits) < 0.005,
        ];
    }

    public function updateSourceDocument(Company $company, Transaction $transaction, Request $request): JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($transaction->company_id === $company->id, 403);

        $validated = $request->validate([
            'source_document' => ['nullable', 'string', 'max:10000'],
        ]);

        $transaction->update(['source_document' => $validated['source_document'] ?? null]);

        return response()->json(['success' => true]);
    }

    public function destroy(Company $company, Transaction $transaction): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($transaction->company_id === $company->id, 403);

        $description = $transaction->description;
        $transaction->journalLines()->delete();
        $transaction->delete();

        return redirect()
            ->route('companies.transactions', $company)
            ->with('success', "Transaction '{$description}' has been deleted.");
    }

    public function bulkDestroy(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $ids = $this->parseTransactionIds($request->input('transaction_ids'));

        if (empty($ids)) {
            return back()->with('error', 'No transactions were selected.');
        }

        $transactions = $company->transactions()->whereIn('id', $ids)->get();

        DB::transaction(function () use ($transactions) {
            foreach ($transactions as $transaction) {
                $transaction->journalLines()->delete();
                $transaction->delete();
            }
        });

        $count = $transactions->count();

        return redirect()
            ->route('companies.transactions', $company)
            ->with('success', "{$count} transaction(s) have been deleted.");
    }

    public function bulkPost(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $ids = $this->parseTransactionIds($request->input('transaction_ids'));

        if (empty($ids)) {
            return back()->with('error', 'No transactions were selected.');
        }

        $transactions = $company->transactions()
            ->whereIn('id', $ids)
            ->where('status', 'draft')
            ->get();

        $posted = 0;
        DB::transaction(function () use ($transactions, &$posted) {
            foreach ($transactions as $transaction) {
                $transaction->update(['status' => 'posted']);
                $posted++;
            }
        });

        return redirect()
            ->route('companies.transactions', $company)
            ->with('success', "{$posted} draft transaction(s) have been posted.");
    }

    public function bulkReverse(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $ids = $this->parseTransactionIds($request->input('transaction_ids'));

        if (empty($ids)) {
            return back()->with('error', 'No transactions were selected.');
        }

        $transactions = $company->transactions()
            ->whereIn('id', $ids)
            ->where('status', 'posted')
            ->whereNull('reversal_of_id')
            ->with('journalLines')
            ->get();

        $reversed = 0;
        DB::transaction(function () use ($company, $transactions, &$reversed) {
            foreach ($transactions as $transaction) {
                if ($transaction->isReversed() || $transaction->isReversal()) {
                    continue;
                }

                $reversal = $company->transactions()->create([
                    'user_id'          => auth()->id(),
                    'transaction_date' => $transaction->transaction_date,
                    'description'      => 'Reversal: ' . $transaction->description,
                    'reference'        => $transaction->reference,
                    'status'           => 'posted',
                    'notes'            => 'Auto-generated reversal of transaction #' . $transaction->id,
                    'reversal_of_id'   => $transaction->id,
                ]);

                $reversal->journalLines()->createMany(
                    $transaction->journalLines->map(fn($line) => [
                        'chart_of_account_id' => $line->chart_of_account_id,
                        'customer_id'         => $line->customer_id,
                        'supplier_id'         => $line->supplier_id,
                        'type'                => $line->type === 'debit' ? 'credit' : 'debit',
                        'amount'              => $line->amount,
                        'description'         => $line->description,
                        'is_vat_line'         => $line->is_vat_line,
                        'vat_rate'            => $line->vat_rate,
                    ])->all()
                );

                $reversal->load('journalLines.account');
                $reversal->searchable();

                $transaction->update(['status' => 'reversed']);
                $transaction->searchable();
                $reversed++;
            }
        });

        return redirect()
            ->route('companies.transactions', $company)
            ->with('success', "{$reversed} transaction(s) have been reversed.");
    }

    /**
     * Accept transaction_ids as either an array or a comma-separated string,
     * returning a clean list of positive integers.
     */
    private function parseTransactionIds(mixed $raw): array
    {
        if (is_array($raw)) {
            $values = $raw;
        } elseif (is_string($raw) && $raw !== '') {
            $values = explode(',', $raw);
        } else {
            return [];
        }

        return collect($values)
            ->map(fn($v) => (int) $v)
            ->filter(fn($v) => $v > 0)
            ->unique()
            ->values()
            ->all();
    }
}
