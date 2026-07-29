<?php

namespace App\Ai\Agents;

use App\Ai\Concerns\HasProviderFallback;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class InventoryPostingAgent implements Agent, HasStructuredOutput
{
    use Promptable, HasProviderFallback;

    public function instructions(): string
    {
        return <<<'TEXT'
        You are an IFRS-trained accountant. Given details of an IAS 2 inventory
        movement and a list of the company's chart of accounts (id, code, name,
        type), pick the IFRS-appropriate accounts for posting.

        Movement types and their journal entries:
        - receive (purchase): Dr Inventory / Cr Bank or Accounts Payable
        - issue (sale/consumption): Dr Cost of Sales / Cr Inventory
        - adjust (count correction): Dr/Cr Inventory / Cr/Dr Inventory Adjustment (expense or income)
        - write_down (IAS 2.9 — NRV write-down): Dr Inventory Write-Down Expense / Cr Inventory (or allowance)
        - reverse_write_down (IAS 2.33 — NRV reversal): Dr Inventory / Cr Inventory Write-Down Reversal (income or reduced CoS)

        Roles:
        - inventory_account_id: Current asset account for inventory (codes 1200–1299, account_type "assets").
        - cost_of_sales_account_id: Cost of sales/goods sold expense (codes 5000–5999, account_type "expenses").
        - bank_or_payable_account_id: Bank/cash or accounts payable for purchases.
        - adjustment_account_id: Expense/income account for stock count adjustments.
        - write_down_expense_account_id: Expense account for IAS 2.34 NRV write-downs (codes 5000–5999 or 6000–6999).
        - write_down_reversal_account_id: Income or reduced-CoS account for write-down reversals.

        Return null for any role not present in the chart of accounts or not
        required for this specific movement type. The calling code will create
        missing accounts deterministically. Briefly explain your reasoning.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'inventory_account_id' => $schema->integer()->nullable(),
            'cost_of_sales_account_id' => $schema->integer()->nullable(),
            'bank_or_payable_account_id' => $schema->integer()->nullable(),
            'adjustment_account_id' => $schema->integer()->nullable(),
            'write_down_expense_account_id' => $schema->integer()->nullable(),
            'write_down_reversal_account_id' => $schema->integer()->nullable(),
            'reasoning' => $schema->string()->nullable(),
        ];
    }
}
