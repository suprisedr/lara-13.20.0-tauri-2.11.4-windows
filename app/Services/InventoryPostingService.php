<?php

namespace App\Services;

use App\Ai\Agents\InventoryPostingAgent;
use App\Enums\StockMovementAction;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryPostingService
{
    public function __construct(private readonly TransactionService $transactions) {}

    public function postMovementWithAi(InventoryMovement $movement, User $user): void
    {
        $item = $movement->inventoryItem;
        $company = $movement->company;
        $accounts = $this->companyAccounts($company);
        $action = $movement->action;
        $response = $this->prompt($movement, $item, $accounts);

        $qty = abs((float) $movement->quantity);
        $unitCost = (float) $movement->unit_cost;
        $total = round($qty * $unitCost, 2);

        $lines = match ($action) {
            StockMovementAction::Receive => $this->receiveLines($company, $response, $total, $item),
            StockMovementAction::Issue => $this->issueLines($company, $response, $total, $item),
            StockMovementAction::Adjust => $this->adjustLines($company, $response, $movement, $item),
            StockMovementAction::WriteDown => $this->writeDownLines($company, $response, $unitCost, $item),
            StockMovementAction::ReverseWriteDown => $this->reverseWriteDownLines($company, $response, $unitCost, $item),
            default => [],
        };

        if (empty($lines)) return;

        $desc = match ($action) {
            StockMovementAction::Receive => 'Inventory received — '.$item->name,
            StockMovementAction::Issue => 'Inventory issued — '.$item->name,
            StockMovementAction::Adjust => 'Stock adjustment — '.$item->name,
            StockMovementAction::WriteDown => 'NRV write-down — '.$item->name,
            StockMovementAction::ReverseWriteDown => 'Write-down reversal — '.$item->name,
            default => 'Inventory movement — '.$item->name,
        };

        DB::transaction(function () use ($company, $user, $movement, $item, $desc, $lines, $response) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $movement->moved_at->format('Y-m-d'),
                'description' => $desc,
                'reference' => $movement->reference ?: 'INV-MOV-'.$movement->id,
                'status' => 'draft',
                'source_document' => 'inventory_movement:'.$movement->id,
                'notes' => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines' => $lines,
            ]);
        });
    }

    private function receiveLines(Company $company, array $response, float $total, InventoryItem $item): array
    {
        $invId = $this->resolveOrCreate($company, $response['inventory_account_id'], 'Inventory — '.$item->name, '1003000', 'assets');
        $bankId = $response['bank_or_payable_account_id'] ?? null;
        if (! $bankId) throw new RuntimeException('AI could not determine a bank/payable account for inventory purchase.');

        return [
            ['chart_of_account_id' => $invId, 'type' => 'debit', 'amount' => $total, 'description' => 'Goods received'],
            ['chart_of_account_id' => $bankId, 'type' => 'credit', 'amount' => $total, 'description' => 'Payment for goods'],
        ];
    }

    private function issueLines(Company $company, array $response, float $total, InventoryItem $item): array
    {
        $cosId = $this->resolveOrCreate($company, $response['cost_of_sales_account_id'], 'Cost of Sales', '5001000', 'expenses');
        $invId = $this->resolveOrCreate($company, $response['inventory_account_id'], 'Inventory — '.$item->name, '1003000', 'assets');

        return [
            ['chart_of_account_id' => $cosId, 'type' => 'debit', 'amount' => $total, 'description' => 'Cost of goods issued'],
            ['chart_of_account_id' => $invId, 'type' => 'credit', 'amount' => $total, 'description' => 'Inventory issued'],
        ];
    }

    private function adjustLines(Company $company, array $response, InventoryMovement $movement, InventoryItem $item): array
    {
        $qty = (float) $movement->quantity;
        $unitCost = (float) $movement->unit_cost;
        $total = round(abs($qty) * $unitCost, 2);

        $invId = $this->resolveOrCreate($company, $response['inventory_account_id'], 'Inventory — '.$item->name, '1003000', 'assets');
        $adjId = $this->resolveOrCreate($company, $response['adjustment_account_id'], 'Inventory Adjustments', '6003000', 'expenses');

        if ($qty > 0) {
            return [
                ['chart_of_account_id' => $invId, 'type' => 'debit', 'amount' => $total, 'description' => 'Stock count surplus'],
                ['chart_of_account_id' => $adjId, 'type' => 'credit', 'amount' => $total, 'description' => 'Inventory adjustment — surplus'],
            ];
        }

        return [
            ['chart_of_account_id' => $adjId, 'type' => 'debit', 'amount' => $total, 'description' => 'Stock count shortage'],
            ['chart_of_account_id' => $invId, 'type' => 'credit', 'amount' => $total, 'description' => 'Inventory adjustment — shortage'],
        ];
    }

    private function writeDownLines(Company $company, array $response, float $amount, InventoryItem $item): array
    {
        if ($amount <= 0) return [];

        $expId = $this->resolveOrCreate($company, $response['write_down_expense_account_id'], 'Inventory Write-Down', '5009000', 'expenses');
        $invId = $this->resolveOrCreate($company, $response['inventory_account_id'], 'Inventory — '.$item->name, '1003000', 'assets');

        return [
            ['chart_of_account_id' => $expId, 'type' => 'debit', 'amount' => $amount, 'description' => 'IAS 2 NRV write-down'],
            ['chart_of_account_id' => $invId, 'type' => 'credit', 'amount' => $amount, 'description' => 'Inventory written down to NRV'],
        ];
    }

    private function reverseWriteDownLines(Company $company, array $response, float $amount, InventoryItem $item): array
    {
        if ($amount <= 0) return [];

        $invId = $this->resolveOrCreate($company, $response['inventory_account_id'], 'Inventory — '.$item->name, '1003000', 'assets');
        $revId = $this->resolveOrCreate($company, $response['write_down_reversal_account_id'], 'Write-Down Reversal — Inventory', '5009010', 'expenses');

        return [
            ['chart_of_account_id' => $invId, 'type' => 'debit', 'amount' => $amount, 'description' => 'IAS 2 write-down reversal'],
            ['chart_of_account_id' => $revId, 'type' => 'credit', 'amount' => $amount, 'description' => 'Write-down reversal — NRV recovered'],
        ];
    }

    private function resolveOrCreate(Company $company, ?int $aiPick, string $name, string $codePrefix, string $type): int
    {
        if ($aiPick && ChartOfAccount::where('company_id', $company->id)->where('id', $aiPick)->exists()) {
            return $aiPick;
        }

        $existing = ChartOfAccount::where('company_id', $company->id)
            ->where('account_name', 'like', $name.'%')
            ->where('account_type', $type)
            ->first();
        if ($existing) return $existing->id;

        $maxCode = ChartOfAccount::where('company_id', $company->id)
            ->where('account_code', 'like', $codePrefix.'%')
            ->max('account_code');
        $code = $maxCode ? (string) ((int) $maxCode + 1) : $codePrefix.'0';

        return ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => $type,
            'is_active' => true,
        ])->id;
    }

    private function companyAccounts(Company $company)
    {
        return $company->chartOfAccounts()
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name', 'account_type', 'is_contra']);
    }

    private function prompt(InventoryMovement $movement, InventoryItem $item, $accounts): array
    {
        $list = $accounts
            ->map(fn (ChartOfAccount $a) => "- id={$a->id}, code={$a->account_code}, name=\"{$a->account_name}\", type={$a->account_type}".($a->is_contra ? ' (contra)' : ''))
            ->implode("\n");

        $action = $movement->action->value;
        $qty = (float) $movement->quantity;
        $unitCost = (float) $movement->unit_cost;

        $details = "Item: {$item->name} (SKU: {$item->sku})\n"
            ."Inventory type: ".($item->inventory_type?->value ?? 'unclassified')."\n"
            ."Movement: {$action}, Quantity: {$qty}, Unit cost: {$unitCost}\n"
            ."Total: ".round(abs($qty) * $unitCost, 2)."\n"
            ."Date: {$movement->moved_at->format('Y-m-d')}\n"
            ."Reference: {$movement->reference}\n";

        $prompt = "Movement type: {$action}\n\n{$details}\nChart of accounts:\n{$list}\n\nPick the IAS 2-appropriate accounts for this inventory movement posting.";

        return (new InventoryPostingAgent)->prompt($prompt)->toArray();
    }
}
