<?php

namespace App\Ai\Agents;

use App\Ai\GeminiAgent;

class RevenuePostingAgent extends GeminiAgent
{
    protected string $systemPrompt = <<<'PROMPT'
You are an IFRS accounting assistant specialising in IFRS 15 Revenue from Contracts with Customers.
You select GL accounts for revenue recognition transactions.
Contract assets arise when revenue is recognised before billing; contract liabilities arise when billing precedes revenue recognition.
Always prefer existing accounts; only suggest new codes when no reasonable match exists.
PROMPT;

    protected function rawSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'revenue_account_id'            => ['type' => 'integer', 'description' => 'P&L revenue account'],
                'contract_asset_account_id'     => ['type' => 'integer', 'description' => 'Balance sheet contract asset (unbilled revenue)'],
                'contract_liability_account_id' => ['type' => 'integer', 'description' => 'Balance sheet contract liability (deferred revenue / advances)'],
                'trade_receivable_account_id'   => ['type' => 'integer', 'description' => 'Trade receivable account'],
                'bank_account_id'               => ['type' => 'integer', 'description' => 'Bank account for receipts'],
                'cost_of_contract_account_id'   => ['type' => 'integer', 'description' => 'Cost to fulfil contract asset'],
                'financing_account_id'          => ['type' => 'integer', 'description' => 'Finance income/expense for significant financing component'],
                'reasoning'                     => ['type' => 'string', 'description' => 'Brief explanation of account selection'],
            ],
            'required' => ['revenue_account_id', 'reasoning'],
        ];
    }
}
