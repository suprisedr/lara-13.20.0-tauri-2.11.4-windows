<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService
{
    /**
     * Record a balanced double-entry transaction.
     *
     * @param  array{
     *     transaction_date: string,
     *     description: string,
     *     reference?: string|null,
     *     notes?: string|null,
     *     lines: array<int, array{chart_of_account_id: int, type: string, amount: float|string, description?: string|null}>
     * } $data
     *
     * @throws InvalidArgumentException
     */
    public function record(Company $company, User $user, array $data): Transaction
    {
        $lines = $data['lines'] ?? [];

        if (count($lines) < 2) {
            throw new InvalidArgumentException('A transaction must have at least two journal lines.');
        }

        foreach ($lines as $line) {
            if (! isset($line['amount']) || bccomp((string) $line['amount'], '0', 2) <= 0) {
                throw new InvalidArgumentException('All journal line amounts must be greater than zero.');
            }

            if (! in_array($line['type'] ?? '', ['debit', 'credit'], true)) {
                throw new InvalidArgumentException('Journal line type must be "debit" or "credit".');
            }
        }

        $accountIds = array_column($lines, 'chart_of_account_id');
        $validCount = $company->chartOfAccounts()->whereIn('id', $accountIds)->count();

        if ($validCount !== count($accountIds)) {
            throw new InvalidArgumentException('One or more accounts do not belong to this company.');
        }

        $postableCount = $company->chartOfAccounts()->postable()->whereIn('id', $accountIds)->count();

        if ($postableCount !== count($accountIds)) {
            throw new InvalidArgumentException('One or more accounts are parent (group) accounts and cannot receive postings. Please select a sub-account.');
        }

        $customerIds = collect($lines)
            ->pluck('customer_id')
            ->filter()
            ->unique()
            ->values();

        if ($customerIds->isNotEmpty()) {
            $validCustomers = $company->customers()->whereIn('id', $customerIds)->count();

            if ($validCustomers !== $customerIds->count()) {
                throw new InvalidArgumentException('One or more customers do not belong to this company.');
            }
        }

        $supplierIds = collect($lines)
            ->pluck('supplier_id')
            ->filter()
            ->unique()
            ->values();

        if ($supplierIds->isNotEmpty()) {
            $validSuppliers = $company->suppliers()->whereIn('id', $supplierIds)->count();

            if ($validSuppliers !== $supplierIds->count()) {
                throw new InvalidArgumentException('One or more suppliers do not belong to this company.');
            }
        }

        $totalDebits = '0.00';
        $totalCredits = '0.00';

        foreach ($lines as $line) {
            $amount = number_format((float) $line['amount'], 2, '.', '');

            if ($line['type'] === 'debit') {
                $totalDebits = bcadd($totalDebits, $amount, 2);
            } else {
                $totalCredits = bcadd($totalCredits, $amount, 2);
            }
        }

        if (bccomp($totalDebits, $totalCredits, 2) !== 0) {
            throw new InvalidArgumentException(
                "Transaction is not balanced. Debits: {$totalDebits}, Credits: {$totalCredits}."
            );
        }

        return DB::transaction(function () use ($company, $user, $data, $lines) {
            /** @var Transaction $transaction */
            $transaction = $company->transactions()->create([
                'user_id' => $user->id,
                'transaction_date' => $data['transaction_date'],
                'description' => $data['description'],
                'reference' => $data['reference'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'notes' => $data['notes'] ?? null,
                'source_document' => $data['source_document'] ?? null,
            ]);

            $transaction->journalLines()->createMany(
                array_map(fn(array $line) => [
                    'chart_of_account_id' => $line['chart_of_account_id'],
                    'customer_id' => isset($line['customer_id']) ? $line['customer_id'] : null,
                    'supplier_id' => isset($line['supplier_id']) ? $line['supplier_id'] : null,
                    'type' => $line['type'],
                    'amount' => number_format((float) $line['amount'], 2, '.', ''),
                    'description' => $line['description'] ?? null,
                    'is_vat_line' => (bool) ($line['is_vat_line'] ?? false),
                    'vat_rate' => isset($line['vat_rate']) ? (float) $line['vat_rate'] : null,
                ], $lines)
            );

            $transaction->load('journalLines.account');
            $transaction->searchable();

            return $transaction;
        });
    }

    /**
     * Reverse a posted transaction by creating a balancing entry with debits/credits swapped.
     */
    public function reverse(Transaction $transaction, User $user): Transaction
    {
        $transaction->load('journalLines');

        return DB::transaction(function () use ($transaction, $user) {
            $reversal = $transaction->company->transactions()->create([
                'user_id' => $user->id,
                'transaction_date' => $transaction->transaction_date,
                'description' => 'Reversal: ' . $transaction->description,
                'reference' => $transaction->reference,
                'status' => 'posted',
                'notes' => 'Auto-generated reversal of transaction #' . $transaction->id,
                'reversal_of_id' => $transaction->id,
            ]);

            $reversal->journalLines()->createMany(
                $transaction->journalLines->map(fn($line) => [
                    'chart_of_account_id' => $line->chart_of_account_id,
                    'customer_id' => $line->customer_id,
                    'supplier_id' => $line->supplier_id,
                    'type' => $line->type === 'debit' ? 'credit' : 'debit',
                    'amount' => $line->amount,
                    'description' => $line->description,
                    'is_vat_line' => $line->is_vat_line,
                    'vat_rate' => $line->vat_rate,
                ])->all()
            );

            $transaction->update(['status' => 'reversed']);

            $reversal->load('journalLines.account');
            $reversal->searchable();
            $transaction->searchable();

            return $reversal;
        });
    }
}
