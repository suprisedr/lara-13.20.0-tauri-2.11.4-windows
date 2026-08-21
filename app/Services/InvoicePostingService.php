<?php

namespace App\Services;

use App\Ai\Agents\CreateChartOfAccountAgent;
use App\Ai\Agents\InvoicePostingAgent;
use App\Enums\InvoiceStatus;
use App\Enums\StockMovementAction;
use App\Events\InvoiceCreated;
use App\Events\InvoiceMarkedPaid;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InventoryMovement;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class InvoicePostingService
{
    public function __construct(
        private TransactionService $transactions,
        private AccountVectorSearchService $accountSearch,
        private ChartOfAccountCodeResolver $codeResolver,
        private readonly AgentHistoryService $agentHistory = new AgentHistoryService,
    ) {}


    /**
     * Apply an invoice status change, undoing any AI-posted journal entries
     * that no longer apply so the books stay in sync with the invoice.
     */
    public function updateStatus(Company $company, Invoice $invoice, User $user, string $newStatus): Invoice
    {
        $oldStatus = InvoiceStatus::from($invoice->status);
        $targetStatus = InvoiceStatus::from($newStatus);

        if ($oldStatus === $targetStatus) {
            return $invoice;
        }

        if (! $oldStatus->canTransitionTo($targetStatus)) {
            throw new InvalidArgumentException(
                "Cannot change invoice status from \"{$oldStatus->label()}\" to \"{$targetStatus->label()}\"."
            );
        }

        $isCancelling = in_array($targetStatus, [InvoiceStatus::Draft, InvoiceStatus::Voided], true);
        $isIssuing = $oldStatus === InvoiceStatus::Draft && $targetStatus->triggersAiPosting();

        $needsInvoiceReversal = $isCancelling && $invoice->posting_transaction_id;
        $needsPaymentReversal = $isCancelling && $invoice->payment_transaction_id;
        $needsRecordedPaymentsReversal = $isCancelling;

        return DB::transaction(function () use ($company, $invoice, $user, $targetStatus, $isIssuing, $isCancelling, $needsInvoiceReversal, $needsPaymentReversal, $needsRecordedPaymentsReversal) {
            if ($needsPaymentReversal) {
                $invoice->payment_transaction_id = $this->undoTransaction($invoice->paymentTransaction, $user);
            }

            if ($needsRecordedPaymentsReversal) {
                foreach ($invoice->payments()->whereNotNull('transaction_id')->get() as $payment) {
                    $this->undoTransaction($payment->transaction, $user);
                    $payment->update(['transaction_id' => null]);
                }
            }

            if ($needsInvoiceReversal) {
                $invoice->posting_transaction_id = $this->undoTransaction($invoice->postingTransaction, $user);
            }

            if ($isCancelling) {
                $this->reverseStockMovements($invoice);
            }

            if ($targetStatus === InvoiceStatus::Paid) {
                $invoice->save();

                if (! $invoice->posting_transaction_id) {
                    app(RoadRunnerInvoicePostingDispatcher::class)->dispatchPosting($invoice);
                }

                $postedTotal = (float) $invoice->payments()->whereNotNull('transaction_id')->sum('amount');
                if (round($invoice->total() - $postedTotal, 2) > 0) {
                    app(RoadRunnerInvoicePostingDispatcher::class)
                        ->dispatchPayment($invoice->fresh('items', 'payments'), $user);
                }
            } else {
                $invoice->status = $targetStatus->value;
                $invoice->save();

                if ($isIssuing) {
                    $this->issueStockForInvoice($company, $invoice, $user);
                }

                if ($targetStatus->triggersAiPosting() && ! $invoice->posting_transaction_id) {
                    app(RoadRunnerInvoicePostingDispatcher::class)->dispatchPosting($invoice);
                }
            }

            return $invoice;
        });
    }

    /**
     * Record a payment received against an issued invoice, posting a
     * balanced journal entry (debit the receiving account, credit the
     * invoice's accounts-receivable account) and updating the invoice's
     * status based on the resulting balance due.
     *
     * @param  array{payment_date: string, amount: float|string, deposit_account_id: int, method?: string|null, notes?: string|null}  $data
     */
    public function recordPayment(Company $company, Invoice $invoice, User $user, array $data): Invoice
    {
        $status = InvoiceStatus::from($invoice->status);

        if (! $status->isOpen()) {
            throw new InvalidArgumentException('Payments can only be recorded against an issued invoice that is pending, partially paid, or overdue.');
        }

        if (! $invoice->posting_transaction_id) {
            throw new InvalidArgumentException('This invoice has not been posted to the ledger yet.');
        }

        $amount = round((float) $data['amount'], 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('The payment amount must be greater than zero.');
        }

        $balanceDue = $invoice->balanceDue();

        if (bccomp((string) $amount, (string) $balanceDue, 2) > 0) {
            throw new InvalidArgumentException('The payment amount cannot exceed the outstanding balance of ' . number_format($balanceDue, 2) . '.');
        }

        $invoice->load('postingTransaction.journalLines');

        $arLine = $invoice->postingTransaction->journalLines
            ->where('type', 'debit')
            ->first(fn($line) => $line->customer_id !== null)
            ?? $invoice->postingTransaction->journalLines->firstWhere('type', 'debit');

        if (! $arLine) {
            throw new InvalidArgumentException('Could not determine the accounts-receivable account for this invoice.');
        }

        return DB::transaction(function () use ($company, $invoice, $user, $data, $amount, $balanceDue, $arLine) {
            $transaction = $this->transactions->record($company, $user, [
                'transaction_date' => $data['payment_date'],
                'description' => 'Payment received for invoice ' . $invoice->invoice_number . ' — ' . $invoice->customer_name,
                'reference' => $invoice->invoice_number,
                'status' => 'draft',
                'source_document' => $invoice->invoice_number,
                'notes' => $data['notes'] ?? null,
                'lines' => [
                    [
                        'chart_of_account_id' => $data['deposit_account_id'],
                        'type' => 'debit',
                        'amount' => $amount,
                        'description' => 'Payment received for invoice ' . $invoice->invoice_number,
                    ],
                    [
                        'chart_of_account_id' => $arLine->chart_of_account_id,
                        'customer_id' => $arLine->customer_id,
                        'type' => 'credit',
                        'amount' => $amount,
                        'description' => 'Payment received for invoice ' . $invoice->invoice_number,
                    ],
                ],
            ]);

            InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'method' => $data['method'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $remaining = round($balanceDue - $amount, 2);

            $invoice->status = $remaining <= 0
                ? InvoiceStatus::Paid->value
                : InvoiceStatus::PartiallyPaid->value;

            $invoice->save();

            return $invoice;
        });
    }

    /**
     * Remove a not-yet-posted draft journal entry outright, or reverse it
     * with a balancing entry if it has already been posted to the ledger.
     * Returns null so the invoice's *_transaction_id field can be cleared.
     */
    private function undoTransaction(\App\Models\Transaction $transaction, User $user): ?int
    {
        if ($transaction->status === 'draft') {
            $transaction->journalLines()->delete();
            $transaction->delete();

            return null;
        }

        $this->transactions->reverse($transaction, $user);

        return null;
    }

    /**
     * Ask the invoice posting AI agent to pick the appropriate accounts for
     * this invoice, then record the resulting journal entry as a draft so a
     * human can review it before it affects the books.
     */
    public function postWithAi(Company $company, Invoice $invoice, User $user): Invoice
    {
        if ($invoice->posting_transaction_id) {
            throw new InvalidArgumentException('This invoice has already been posted.');
        }

        $accounts = $this->accountSearch->searchMultiple($company->id, [
            'accounts receivable debtors control asset',
            'sales revenue income',
            'VAT output tax liability',
            'cost of sales cost of goods sold expense',
            'inventory stock current asset',
        ], 12);

        if ($accounts->isEmpty()) {
            throw new InvalidArgumentException('This company has no postable chart of accounts to choose from.');
        }

        $subtotal = $invoice->subtotal();
        $tax = $invoice->taxTotal();
        $total = $invoice->total();

        $accountsList = $accounts
            ->map(fn($account) => "- id={$account->id}, code={$account->account_code}, name=\"{$account->account_name}\", type={$account->account_type}")
            ->implode("\n");

        $invoice->load('items.inventoryItem');

        // Build item list including cost info so the AI can determine whether CoS applies
        $totalCogs = 0.0;
        $hasPhysicalItems = false;

        $itemsList = $invoice->items->map(function ($item) use (&$totalCogs, &$hasPhysicalItems) {
            $isService = $item->inventoryItem?->is_service ?? true;
            $cost = $isService ? null : (float) ($item->inventoryItem?->purchase_cost ?: $item->inventoryItem?->unit_price ?? 0);
            $lineCoGs = $cost !== null ? round($cost * (float) $item->quantity, 2) : null;

            if ($lineCoGs !== null) {
                $totalCogs += $lineCoGs;
                $hasPhysicalItems = true;
            }

            $type = $isService ? 'service' : 'physical product';
            $costStr = $lineCoGs !== null ? ", cost of goods = {$lineCoGs}" : '';

            return "- {$item->description} [{$type}]: qty {$item->quantity} x "
                . number_format((float) $item->unit_price, 2)
                . (($item->tax_rate !== null) ? " (VAT {$item->tax_rate}%)" : ' (no VAT)')
                . $costStr;
        })->implode("\n");

        $totalCogs = round($totalCogs, 2);
        $cogsContext = $hasPhysicalItems
            ? "\nTotal cost of goods sold: {$totalCogs} — post Dr Cost of Sales / Cr Inventory for this amount."
            : "\nAll items are services — no Cost of Sales entry required.";

        $prompt = <<<TEXT
        Invoice {$invoice->invoice_number} for customer "{$invoice->customer_name}" dated {$invoice->invoice_date->format('Y-m-d')}, status "{$invoice->status}".

        Line items:
        {$itemsList}

        Subtotal: {$subtotal}
        VAT total: {$tax}
        Invoice total: {$total}
        {$cogsContext}

        Chart of accounts:
        {$accountsList}

        Pick the accounts for posting this invoice.
        TEXT;

        $related = CreditNote::where('invoice_id', $invoice->id)->pluck('id')
            ->map(fn ($cnId) => [
                'agent_type'  => 'credit_note_posting',
                'entity_type' => 'credit_note',
                'entity_id'   => $cnId,
                'label'       => "Credit note #{$cnId} against this invoice",
            ])->all();
        $history = $this->agentHistory->recallWithRelated('invoice_posting', 'invoice', $invoice->id, $related);
        $fullPrompt = $history ? $history."\n\n".$prompt : $prompt;

        $response = (new InvoicePostingAgent)->prompt($fullPrompt);
        $this->agentHistory->remember('invoice_posting', 'invoice', $invoice->id, $prompt, json_encode($response));

        $validAccountIds = $accounts->pluck('id')->all();

        $arAccountId    = $response['accounts_receivable_account_id'] ?? null;
        $salesAccountId = $response['sales_account_id'] ?? null;
        $vatAccountId   = $response['vat_output_account_id'] ?? null;
        $cosAccountId   = $response['cost_of_sales_account_id'] ?? null;
        $invAccountId   = $response['inventory_account_id'] ?? null;

        // Discard any hallucinated ids the agent picked that aren't actually
        // in the supplied account list, before deciding what's still missing.
        $discardInvalid = fn (?int $id) => ($id !== null && in_array($id, $validAccountIds, true)) ? $id : null;
        $arAccountId    = $discardInvalid($arAccountId);
        $salesAccountId = $discardInvalid($salesAccountId);
        $vatAccountId   = $discardInvalid($vatAccountId);
        $cosAccountId   = $discardInvalid($cosAccountId);
        $invAccountId   = $discardInvalid($invAccountId);

        if (! $arAccountId && ($hint = $response['accounts_receivable_account_hint'] ?? null)) {
            $arAccountId = $this->createMissingAccount($company, 'assets', null, $hint, $validAccountIds);
        }

        if (! $salesAccountId && ($hint = $response['sales_account_hint'] ?? null)) {
            $salesAccountId = $this->createMissingAccount($company, 'income', null, $hint, $validAccountIds);
        }

        if (! $arAccountId || ! $salesAccountId) {
            throw new RuntimeException('The AI agent could not determine valid accounts for this invoice. Please post it manually.');
        }

        if ($tax > 0 && ! $vatAccountId && ($hint = $response['vat_output_account_hint'] ?? null)) {
            $vatAccountId = $this->createMissingAccount($company, 'liabilities', null, $hint, $validAccountIds);
        }

        if ($tax > 0 && ! $vatAccountId) {
            throw new RuntimeException('The AI agent could not determine a VAT output account for this invoice. Please post it manually.');
        }

        if ($hasPhysicalItems && $totalCogs > 0) {
            if (! $cosAccountId && ($hint = $response['cost_of_sales_account_hint'] ?? null)) {
                $cosAccountId = $this->createMissingAccount($company, 'expenses', 'cost_of_sales', $hint, $validAccountIds);
            }
            if ($cosAccountId && ! $invAccountId && ($hint = $response['inventory_account_hint'] ?? null)) {
                $invAccountId = $this->createMissingAccount($company, 'assets', null, $hint, $validAccountIds);
            }
        }

        $lines = [
            [
                'chart_of_account_id' => $arAccountId,
                'customer_id' => $invoice->customer_id,
                'type' => 'debit',
                'amount' => $total,
                'description' => 'Invoice ' . $invoice->invoice_number,
            ],
            [
                'chart_of_account_id' => $salesAccountId,
                'type' => 'credit',
                'amount' => $subtotal,
                'description' => 'Invoice ' . $invoice->invoice_number,
            ],
        ];

        if ($tax > 0) {
            $lines[] = [
                'chart_of_account_id' => $vatAccountId,
                'type' => 'credit',
                'amount' => $tax,
                'description' => 'VAT on invoice ' . $invoice->invoice_number,
                'is_vat_line' => true,
            ];
        }

        // Post cost of sales if the AI identified a CoS account and there are physical goods
        if ($hasPhysicalItems && $totalCogs > 0 && $cosAccountId) {
            $lines[] = [
                'chart_of_account_id' => $cosAccountId,
                'type' => 'debit',
                'amount' => $totalCogs,
                'description' => 'Cost of sales — invoice ' . $invoice->invoice_number,
            ];

            if ($invAccountId) {
                $lines[] = [
                    'chart_of_account_id' => $invAccountId,
                    'type' => 'credit',
                    'amount' => $totalCogs,
                    'description' => 'Inventory issued — invoice ' . $invoice->invoice_number,
                ];
            }
        }

        return DB::transaction(function () use ($company, $invoice, $user, $lines, $response) {
            $transaction = $this->transactions->record($company, $user, [
                'transaction_date' => $invoice->invoice_date->format('Y-m-d'),
                'description' => 'Invoice ' . $invoice->invoice_number . ' — ' . $invoice->customer_name,
                'reference' => $invoice->invoice_number,
                'status' => 'draft',
                'source_document' => $invoice->invoice_number,
                'notes' => 'Posted by AI agent: ' . ($response['reasoning'] ?? ''),
                'lines' => $lines,
            ]);

            $invoice->posting_transaction_id = $transaction->id;
            $invoice->save();

            return $invoice;
        });
    }

    /**
     * Create a chart-of-account entry that a posting agent flagged as
     * missing, via the CreateChartOfAccountAgent, and return its new id.
     * The account_type (and, for expenses, the IFRS expense band) is fixed
     * by the caller — the agent only decides the name/category/parent.
     */
    private function createMissingAccount(Company $company, string $accountType, ?string $expenseClass, string $hint, array &$validAccountIds): int
    {
        $existingGroups = $company->chartOfAccounts()
            ->where('account_type', $accountType)
            ->whereNull('parent_id')
            ->orderBy('account_code')
            ->get(['account_code', 'account_name']);

        $groupsList = $existingGroups->isEmpty()
            ? '(none yet)'
            : $existingGroups->map(fn ($a) => "- code={$a->account_code}, name=\"{$a->account_name}\"")->implode("\n");

        $expenseNote = $expenseClass ? " (IFRS expense band: {$expenseClass})" : '';

        $prompt = <<<TEXT
        A new {$accountType} account{$expenseNote} is needed: {$hint}

        Existing top-level {$accountType} accounts for this company:
        {$groupsList}
        TEXT;

        $suggestion = (new CreateChartOfAccountAgent($company))->prompt($prompt);

        $parent = ! empty($suggestion['parent_code'])
            ? $company->chartOfAccounts()->where('account_code', $suggestion['parent_code'])->whereNull('parent_id')->first()
            : null;

        $code = $parent
            ? $this->codeResolver->nextChildCode($company, $parent)
            : $this->codeResolver->nextTopLevelCode($company, $accountType, $expenseClass);

        $account = $company->chartOfAccounts()->create([
            'account_code' => $code,
            'account_name' => $suggestion['account_name'],
            'account_type' => $accountType,
            'category' => $suggestion['category'] ?? null,
            'description' => $suggestion['description'] ?? null,
            'parent_id' => $parent?->id,
            'parent_code' => $parent?->account_code,
            'is_contra' => $suggestion['is_contra'] ?? false,
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        $validAccountIds[] = $account->id;

        return $account->id;
    }

    /**
     * Ask the invoice posting AI to pick a bank/cash account and post the
     * payment journal entry (Dr Bank / Cr AR) for the full outstanding balance.
     * Called automatically when an invoice is marked Paid directly via the
     * status dropdown (as opposed to going through recordPayment()).
     */
    public function postPaymentWithAi(Company $company, Invoice $invoice, User $user, ?float $amount = null): void
    {
        $postedPayments = (float) $invoice->payments()->whereNotNull('transaction_id')->sum('amount');
        $balanceDue = round($invoice->total() - $postedPayments, 2);

        if ($balanceDue <= 0) {
            return;
        }

        $paymentAmount = $amount !== null ? round($amount, 2) : $balanceDue;

        if ($paymentAmount <= 0 || $paymentAmount > $balanceDue) {
            throw new \InvalidArgumentException("Payment amount must be between 0.01 and {$balanceDue}.");
        }

        if (! $invoice->posting_transaction_id) {
            throw new \InvalidArgumentException('Cannot post payment — invoice has not been posted to the ledger yet.');
        }

        $invoice->load('postingTransaction.journalLines');

        $arLine = $invoice->postingTransaction->journalLines
            ->where('type', 'debit')
            ->first(fn($line) => $line->customer_id !== null)
            ?? $invoice->postingTransaction->journalLines->firstWhere('type', 'debit');

        if (! $arLine) {
            throw new \InvalidArgumentException('Could not determine the accounts-receivable account for this invoice.');
        }

        $accounts = $this->accountSearch->searchMultiple($company->id, [
            'bank cash asset current account',
            'accounts receivable debtors control',
        ], 12);

        $accountsList = $accounts
            ->map(fn($a) => "- id={$a->id}, code={$a->account_code}, name=\"{$a->account_name}\", type={$a->account_type}")
            ->implode("\n");

        $total = $invoice->total();
        $paymentType = $paymentAmount < $balanceDue ? 'PARTIAL' : 'FULL';
        $prompt = <<<TEXT
        Invoice {$invoice->invoice_number} for customer \"{$invoice->customer_name}\" — payment received.
        Invoice total: {$total}. Outstanding balance: {$balanceDue}. Payment amount: {$paymentAmount}.
        This is a {$paymentType} payment.

        Post the payment receipt journal entry. You must choose a bank_account_id (the
        bank or cash asset account to debit). The accounts-receivable account is already
        known (id={$arLine->chart_of_account_id}) — you do not need to choose it, but
        still return a plausible accounts_receivable_account_id and sales_account_id.

        Chart of accounts:
        {$accountsList}
        TEXT;

        $related = CreditNote::where('invoice_id', $invoice->id)->pluck('id')
            ->map(fn ($cnId) => [
                'agent_type'  => 'credit_note_posting',
                'entity_type' => 'credit_note',
                'entity_id'   => $cnId,
                'label'       => "Credit note #{$cnId} against this invoice",
            ])->all();
        $history = $this->agentHistory->recallWithRelated('invoice_posting', 'invoice', $invoice->id, $related);
        $fullPrompt = $history ? $history."\n\n".$prompt : $prompt;

        $response = (new \App\Ai\Agents\InvoicePostingAgent)->prompt($fullPrompt);
        $this->agentHistory->remember('invoice_posting', 'invoice', $invoice->id, $prompt, json_encode($response));

        $bankAccountId = $response['bank_account_id'] ?? null;
        $validIds = $accounts->pluck('id')->all();

        if ($bankAccountId !== null && ! in_array($bankAccountId, $validIds, true)) {
            $bankAccountId = null;
        }

        if (! $bankAccountId && ($hint = $response['bank_account_hint'] ?? null)) {
            $bankAccountId = $this->createMissingAccount($company, 'assets', null, $hint, $validIds);
        }

        if (! $bankAccountId) {
            $bankAccount = $accounts->first(fn($a) =>
                $a->account_type === 'assets' &&
                (str_contains(strtolower($a->account_name), 'bank') ||
                 str_contains(strtolower($a->account_name), 'cash'))
            );
            if (! $bankAccount) {
                $bankAccount = ChartOfAccount::where('company_id', $company->id)
                    ->where('account_type', 'assets')
                    ->whereNotIn('id', fn($sub) => $sub->select('parent_id')->from('chart_of_accounts')->whereNotNull('parent_id'))
                    ->where(fn($q) => $q->where('account_name', 'like', '%bank%')
                        ->orWhere('account_name', 'like', '%cash%'))
                    ->first();
            }
            $bankAccountId = $bankAccount?->id;
        }

        if (! $bankAccountId) {
            throw new \RuntimeException('Could not determine a bank account for the payment entry.');
        }

        DB::transaction(function () use ($company, $invoice, $user, $bankAccountId, $arLine, $paymentAmount, $response) {
            $transaction = $this->transactions->record($company, $user, [
                'transaction_date' => now()->toDateString(),
                'description'      => 'Payment received — Invoice ' . $invoice->invoice_number . ' — ' . $invoice->customer_name,
                'reference'        => $invoice->invoice_number,
                'status'           => 'draft',
                'source_document'  => $invoice->invoice_number,
                'notes'            => 'Posted by AI agent: ' . ($response['reasoning'] ?? 'payment receipt entry'),
                'lines'            => [
                    [
                        'chart_of_account_id' => $bankAccountId,
                        'type'                => 'debit',
                        'amount'              => $paymentAmount,
                        'description'         => 'Payment received — ' . $invoice->invoice_number,
                    ],
                    [
                        'chart_of_account_id' => $arLine->chart_of_account_id,
                        'customer_id'         => $arLine->customer_id,
                        'type'                => 'credit',
                        'amount'              => $paymentAmount,
                        'description'         => 'Payment received — ' . $invoice->invoice_number,
                    ],
                ],
            ]);

            \App\Models\InvoicePayment::create([
                'invoice_id'   => $invoice->id,
                'transaction_id' => $transaction->id,
                'user_id'      => $user->id,
                'payment_date' => now()->toDateString(),
                'amount'       => $paymentAmount,
                'method'       => null,
                'notes'        => 'Auto-posted by AI agent.',
            ]);

            $invoice->payment_transaction_id = $transaction->id;

            $remaining = $invoice->balanceDue();
            if ($remaining <= 0) {
                $invoice->status = InvoiceStatus::Paid->value;
            } else {
                $invoice->status = InvoiceStatus::PartiallyPaid->value;
            }

            $invoice->save();
        });
    }

    private function issueStockForInvoice(Company $company, Invoice $invoice, User $user): void
    {
        $invoice->load('items.inventoryItem');

        foreach ($invoice->items as $line) {
            if (! $line->inventoryItem || $line->inventoryItem->is_service) {
                continue;
            }

            $item = $line->inventoryItem;
            $qty = (float) $line->quantity;

            InventoryMovement::create([
                'company_id'        => $company->id,
                'inventory_item_id' => $item->id,
                'invoice_id'        => $invoice->id,
                'action'            => StockMovementAction::Issue,
                'quantity'          => -$qty,
                'unit_cost'         => (float) ($item->purchase_cost ?: $item->unit_price),
                'reference'         => $invoice->invoice_number,
                'notes'             => 'Auto-issued for invoice ' . $invoice->invoice_number,
                'moved_at'          => $invoice->invoice_date,
                'created_by'        => $user->id,
            ]);

            $item->decrement('quantity_on_hand', $qty);
        }
    }

    private function reverseStockMovements(Invoice $invoice): void
    {
        $movements = InventoryMovement::where('invoice_id', $invoice->id)->get();

        foreach ($movements as $movement) {
            $movement->inventoryItem?->increment('quantity_on_hand', abs((float) $movement->quantity));
            $movement->delete();
        }
    }
}
