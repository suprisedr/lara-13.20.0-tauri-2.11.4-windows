<?php

namespace App\Ai\Agents;

use App\Ai\GeminiAgent;

class ShareBasedPaymentPostingAgent extends GeminiAgent
{
    protected string $systemPrompt = <<<'PROMPT'
You are an IFRS accounting assistant specialising in IFRS 2 Share-Based Payment.
You select GL accounts for share-based payment transactions.
For equity-settled arrangements, recognise the expense in P&L with a corresponding credit to equity (share-based payment reserve).
For cash-settled arrangements, recognise the expense in P&L with a corresponding credit to a liability.
On exercise, debit the equity reserve and credit share capital / bank.
Always prefer existing accounts; only suggest new codes when no reasonable match exists.
PROMPT;

    protected function rawSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'expense_account_id'        => ['type' => 'integer', 'description' => 'P&L expense account for share-based payment expense (IFRS 2.7)'],
                'equity_reserve_account_id' => ['type' => 'integer', 'description' => 'Equity reserve account for equity-settled arrangements (IFRS 2.10)'],
                'liability_account_id'      => ['type' => 'integer', 'description' => 'Liability account for cash-settled arrangements (IFRS 2.30)'],
                'bank_account_id'           => ['type' => 'integer', 'description' => 'Bank / cash account for exercise proceeds'],
                'share_capital_account_id'  => ['type' => 'integer', 'description' => 'Share capital account for equity instruments issued on exercise'],
                'reasoning'                 => ['type' => 'string', 'description' => 'Brief explanation of account selection'],
            ],
            'required' => ['expense_account_id', 'reasoning'],
        ];
    }
}
