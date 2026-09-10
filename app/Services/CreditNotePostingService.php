<?php

namespace App\Services;

use App\Ai\Agents\CreditNotePostingAgent;
use App\Events\PostingStatusUpdated;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\User;

class CreditNotePostingService
{
    public function __construct(
        private readonly TransactionService        $transactions,
        private readonly AccountVectorSearchService $accountSearch,
        private readonly AgentHistoryService        $agentHistory = new AgentHistoryService,
    ) {}

    public function post(Company $company, User $user, CreditNote $creditNote): void
    {
        $creditNote->load('items.inventoryItem');

        $sourceDoc = 'credit_note:' . $creditNote->id;

        // Idempotency — don't double-post
        if ($creditNote->posting_transaction_id) {
            return;
        }

        $subtotal = $creditNote->subtotal();
        $tax      = $creditNote->taxTotal();
        $total    = $creditNote->total();

        $hasPhysicalReturns = $creditNote->items->contains(
            fn($i) => $i->return_to_stock && $i->inventoryItem && !$i->inventoryItem->is_service
        );

        $totalCogs = 0.0;
        if ($hasPhysicalReturns) {
            foreach ($creditNote->items as $item) {
                if (!$item->return_to_stock || !$item->inventoryItem || $item->inventoryItem->is_service) continue;
                $cost = (float) ($item->inventoryItem->purchase_cost ?: $item->inventoryItem->unit_price ?? 0);
                $totalCogs += round($cost * (float) $item->quantity, 2);
            }
        }

        $accounts = $this->accountSearch->searchMultiple($company->id, [
            'revenue sales income accounts',
            'accounts receivable debtors trade receivable',
            'VAT output tax liability',
            'inventory stock asset',
            'cost of sales cost of goods sold',
            'accounts payable creditor refund payable liability',
        ], 12);

        $list = $accounts
            ->map(fn(ChartOfAccount $a) => "- id={$a->id}, code={$a->account_code}, name=\"{$a->account_name}\", type={$a->account_type}")
            ->implode("\n");

        $itemLines = $creditNote->items->map(function ($item) {
            $type = ($item->inventoryItem?->is_service ?? true) ? 'service' : 'physical';
            return "  - {$item->description} | qty={$item->quantity} | unit_price={$item->unit_price} | type={$type} | return_to_stock=" . ($item->return_to_stock ? 'yes' : 'no');
        })->implode("\n");

        $invoicePaymentContext = '';
        if ($creditNote->invoice_id) {
            $invoice = $creditNote->invoice;
            if ($invoice) {
                $invoicePaid = (float) $invoice->payments()->whereNotNull('transaction_id')->sum('amount');
                $invoiceTotal = $invoice->total();
                $arBalance = round($invoiceTotal - $invoicePaid, 2);
                $invoicePaymentContext = "\n        Original invoice: {$invoice->invoice_number} (total: {$invoiceTotal})"
                    ."\n        Invoice status: {$invoice->status}"
                    ."\n        Amount already collected from customer: {$invoicePaid}"
                    ."\n        Trade Debtors balance for this invoice: {$arBalance}";

                if ($arBalance <= 0) {
                    $invoicePaymentContext .= "\n\n        IMPORTANT: The customer has already paid the full invoice amount."
                        ."\n        Trade Debtors balance is zero — do NOT credit Trade Debtors."
                        ."\n        Credit Accounts Payable or Refund Payable instead, since the company now owes the customer a refund.";
                } elseif ($arBalance < $total) {
                    $partialRefund = round($total - $arBalance, 2);
                    $invoicePaymentContext .= "\n\n        IMPORTANT: The credit note ({$total}) exceeds the remaining Trade Debtors balance ({$arBalance})."
                        ."\n        Credit Trade Debtors only up to {$arBalance}, and credit Accounts Payable / Refund Payable for the excess {$partialRefund}.";
                }
            }
        }

        $prompt = <<<TEXT
        Posting type: credit_note

        Company: {$company->registered_name}
        Credit Note: {$creditNote->credit_note_number}
        Date: {$creditNote->credit_note_date}
        Customer: {$creditNote->customer_name}
        Reason: {$creditNote->reason}

        Subtotal (excl. VAT): {$subtotal}
        VAT:                  {$tax}
        Total credit:         {$total}
        Has physical returns: {$hasPhysicalReturns}
        Total COGS to reverse: {$totalCogs}{$invoicePaymentContext}

        Line items:
        {$itemLines}

        Chart of accounts:
        {$list}

        Select accounts for the credit note journal entry. Only return inventory/CoS accounts if physical goods are returned to stock.
        TEXT;

        $related = [];
        if ($creditNote->invoice_id) {
            $related[] = [
                'agent_type'  => 'invoice_posting',
                'entity_type' => 'invoice',
                'entity_id'   => $creditNote->invoice_id,
                'label'       => "Original invoice #{$creditNote->invoice_id}",
            ];
        }
        $history = $this->agentHistory->recallWithRelated('credit_note_posting', 'credit_note', $creditNote->id, $related);
        $fullPrompt = $history ? $history."\n\n".$prompt : $prompt;

        $response = (new CreditNotePostingAgent)->prompt($fullPrompt)->toArray();
        $this->agentHistory->remember('credit_note_posting', 'credit_note', $creditNote->id, $prompt, json_encode($response));

        $revenueId   = $response['revenue_account_id'] ?? null;
        $arId        = $response['ar_account_id']      ?? null;
        $vatId       = $response['vat_account_id']     ?? null;
        $inventoryId = $response['inventory_account_id'] ?? null;
        $cosId       = $response['cos_account_id']     ?? null;
        $apId        = $response['ap_account_id']      ?? null;

        $arBalance = null;
        if ($creditNote->invoice_id && $creditNote->invoice) {
            $invoice = $creditNote->invoice;
            $invoicePaid = (float) $invoice->payments()->whereNotNull('transaction_id')->sum('amount');
            $arBalance = round($invoice->total() - $invoicePaid, 2);
        }

        if (!$apId) {
            $apAccount = $accounts->first(fn(ChartOfAccount $a) =>
                $a->account_type === 'liabilities' && !$a->is_group &&
                (str_contains(strtolower($a->account_name), 'payable') ||
                 str_contains(strtolower($a->account_name), 'creditor') ||
                 str_contains(strtolower($a->account_name), 'refund'))
            );
            if (!$apAccount) {
                $apAccount = ChartOfAccount::where('company_id', $company->id)
                    ->where('account_type', 'liabilities')
                    ->whereNotIn('id', fn($sub) => $sub->select('parent_id')->from('chart_of_accounts')->whereNotNull('parent_id'))
                    ->where(fn($q) => $q->where('account_name', 'like', '%payable%')
                        ->orWhere('account_name', 'like', '%creditor%')
                        ->orWhere('account_name', 'like', '%refund%'))
                    ->first();
            }
            if ($apAccount) {
                $apId = $apAccount->id;
            }
        }

        $invoiceFullyPaid = $arBalance !== null && $arBalance <= 0;

        if (!$revenueId || (!$arId && !$invoiceFullyPaid)) {
            \Illuminate\Support\Facades\Log::warning('CreditNotePostingService: agent did not return required accounts', $response);
            return;
        }

        if ($invoiceFullyPaid && !$apId) {
            \Illuminate\Support\Facades\Log::warning('CreditNotePostingService: invoice fully paid but no AP account found', $response);
            return;
        }

        // Build journal lines
        // Dr Revenue (reverse), Dr VAT (reverse), Cr AR or AP
        $lines = [];

        $lines[] = ['chart_of_account_id' => $revenueId, 'type' => 'debit',  'amount' => $subtotal, 'description' => 'Revenue reversal — ' . $creditNote->credit_note_number];
        if ($tax > 0 && $vatId) {
            $lines[] = ['chart_of_account_id' => $vatId, 'type' => 'debit', 'amount' => $tax, 'description' => 'VAT reversal — ' . $creditNote->credit_note_number];
        }

        if ($arBalance !== null && $arBalance <= 0 && $apId) {
            $lines[] = ['chart_of_account_id' => $apId, 'type' => 'credit', 'amount' => $total, 'description' => 'Refund payable — ' . $creditNote->credit_note_number];
        } elseif ($arBalance !== null && $arBalance > 0 && $arBalance < $total && $apId) {
            $lines[] = ['chart_of_account_id' => $arId, 'type' => 'credit', 'amount' => $arBalance, 'description' => 'AR reduction — ' . $creditNote->credit_note_number];
            $excess = round($total - $arBalance, 2);
            $lines[] = ['chart_of_account_id' => $apId, 'type' => 'credit', 'amount' => $excess, 'description' => 'Refund payable — ' . $creditNote->credit_note_number];
        } else {
            $lines[] = ['chart_of_account_id' => $arId, 'type' => 'credit', 'amount' => $total, 'description' => 'AR reduction — ' . $creditNote->credit_note_number];
        }

        // Dr Inventory / Cr CoS for physical returns
        if ($hasPhysicalReturns && $totalCogs > 0 && $inventoryId && $cosId) {
            $lines[] = ['chart_of_account_id' => $inventoryId, 'type' => 'debit',  'amount' => $totalCogs, 'description' => 'Inventory return — ' . $creditNote->credit_note_number];
            $lines[] = ['chart_of_account_id' => $cosId,       'type' => 'credit', 'amount' => $totalCogs, 'description' => 'CoS reversal — ' . $creditNote->credit_note_number];
        }

        $transaction = $this->transactions->record($company, $user, [
            'transaction_date' => $creditNote->credit_note_date->toDateString(),
            'description'      => 'Credit Note ' . $creditNote->credit_note_number . ' — ' . $creditNote->customer_name,
            'reference'        => $creditNote->credit_note_number,
            'source_document'  => $sourceDoc,
            'notes'            => 'Posted by AI agent: ' . ($response['reasoning'] ?? ''),
            'lines'            => $lines,
        ]);

        $creditNote->update(['posting_transaction_id' => $transaction->id]);

        event(new PostingStatusUpdated(
            $company->id,
            'credit_note',
            $creditNote->id,
            'posted',
            'Credit note posted — ' . $creditNote->credit_note_number,
        ));
    }
}
