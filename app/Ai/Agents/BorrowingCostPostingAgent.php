<?php

namespace App\Ai\Agents;

use App\Ai\GeminiAgent;

class BorrowingCostPostingAgent extends GeminiAgent
{
    protected string $systemPrompt = <<<'PROMPT'
You are an IFRS accounting assistant specialising in IAS 23 Borrowing Costs.
You select GL accounts for borrowing cost capitalisation transactions.
IAS 23 requires that borrowing costs directly attributable to the acquisition, construction, or production of a qualifying asset be capitalised as part of the cost of that asset.
Always prefer existing accounts; only suggest new codes when no reasonable match exists.
PROMPT;

    protected function rawSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'asset_cost_account_id'      => ['type' => 'integer', 'description' => 'GL account for the qualifying asset cost (to capitalise borrowing costs into)'],
                'interest_expense_account_id' => ['type' => 'integer', 'description' => 'P&L interest/finance cost expense account to credit (reverse the expense)'],
                'reasoning'                  => ['type' => 'string', 'description' => 'Brief explanation of account selection'],
            ],
            'required' => ['asset_cost_account_id', 'interest_expense_account_id', 'reasoning'],
        ];
    }
}
