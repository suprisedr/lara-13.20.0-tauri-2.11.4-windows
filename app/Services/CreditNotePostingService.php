<?php

namespace App\Services;

use App\Ai\Agents\CreditNotePostingAgent;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\User;

class CreditNotePostingService
{
    public function __construct(
        private readonly TransactionService        $transactions,
        private readonly AccountVectorSearchService $accountSearch,
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
        ], 12);

        $list = $accounts
            ->map(fn(ChartOfAccount $a) => "- id={$a->id}, code={$a->account_code}, name=\"{$a->account_name}\", type={$a->account_type}")
            ->implode("\n");

        $itemLines = $creditNote->items->map(function ($item) {
            $type = ($item->inventoryItem?->is_service ?? true) ? 'service' : 'physical';
            return "  - {$item->description} | qty={$item->quantity} | unit_price={$item->unit_price} | type={$type} | return_to_stock=" . ($item->return_to_stock ? 'yes' : 'no');
        })->implode("\n");

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
        Total COGS to reverse: {$totalCogs}

        Line items:
        {$itemLines}

        Chart of accounts:
        {$list}

        Select accounts for the credit note journal entry. Only return inventory/CoS accounts if physical goods are returned to stock.
        TEXT;

        $response = (new CreditNotePostingAgent)->prompt($prompt)->toArray();

        $revenueId   = $response['revenue_account_id'] ?? null;
        $arId        = $response['ar_account_id']      ?? null;
        $vatId       = $response['vat_account_id']     ?? null;
        $inventoryId = $response['inventory_account_id'] ?? null;
        $cosId       = $response['cos_account_id']     ?? null;

        if (!$revenueId || !$arId) {
            \Illuminate\Support\Facades\Log::warning('CreditNotePostingService: agent did not return required accounts', $response);
            return;
        }

        // Build journal lines
        // Dr Revenue (reverse), Dr VAT (reverse), Cr AR
        $lines = [];

        $lines[] = ['chart_of_account_id' => $revenueId, 'type' => 'debit',  'amount' => $subtotal, 'description' => 'Revenue reversal — ' . $creditNote->credit_note_number];
        if ($tax > 0 && $vatId) {
            $lines[] = ['chart_of_account_id' => $vatId, 'type' => 'debit', 'amount' => $tax, 'description' => 'VAT reversal — ' . $creditNote->credit_note_number];
        }
        $lines[] = ['chart_of_account_id' => $arId, 'type' => 'credit', 'amount' => $total, 'description' => 'AR reduction — ' . $creditNote->credit_note_number];

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
    }
}
