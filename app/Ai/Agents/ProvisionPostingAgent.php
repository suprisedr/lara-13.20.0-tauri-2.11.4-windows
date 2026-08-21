<?php

namespace App\Ai\Agents;

use App\Ai\GeminiAgent;

class ProvisionPostingAgent extends GeminiAgent
{
    protected string $systemPrompt = <<<'PROMPT'
You are an IFRS accounting assistant specialising in IAS 37 Provisions, Contingent Liabilities and Contingent Assets.
You select GL accounts for provision-related transactions.
Always prefer existing accounts; only suggest new codes when no reasonable match exists.
PROMPT;

    protected function rawSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'provision_liability_account_id' => ['type' => 'integer', 'description' => 'BS liability account for the provision (IAS 37.14)'],
                'provision_expense_account_id'   => ['type' => 'integer', 'description' => 'P&L expense account for provision recognition / remeasurement'],
                'unwinding_expense_account_id'   => ['type' => 'integer', 'description' => 'P&L finance cost account for unwinding of discount (IAS 37.60)'],
                'bank_or_payable_account_id'     => ['type' => 'integer', 'description' => 'Bank / accounts payable for utilisation payments'],
                'reversal_income_account_id'     => ['type' => 'integer', 'description' => 'P&L income account for reversal of provision (IAS 37.59)'],
                'reasoning'                      => ['type' => 'string', 'description' => 'Brief explanation of account selection'],
            ],
            'required' => ['provision_liability_account_id', 'provision_expense_account_id', 'reasoning'],
        ];
    }
}
