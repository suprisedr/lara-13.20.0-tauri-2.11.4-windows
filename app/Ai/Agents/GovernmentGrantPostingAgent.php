<?php

namespace App\Ai\Agents;

use App\Ai\GeminiAgent;

class GovernmentGrantPostingAgent extends GeminiAgent
{
    protected string $systemPrompt = <<<'PROMPT'
You are an IFRS accounting assistant specialising in IAS 20 Government Grants.
You select GL accounts for government grant-related transactions.
Government grants are recognised when there is reasonable assurance that conditions will be met and the grant will be received.
Income grants are recognised in profit or loss on a systematic basis over the periods in which the related costs are recognised.
Asset grants are presented as deferred income, then recognised in profit or loss over the useful life of the related asset.
Always prefer existing accounts; only suggest new codes when no reasonable match exists.
PROMPT;

    protected function rawSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'deferred_income_account_id'    => ['type' => 'integer', 'description' => 'BS liability account for deferred grant income (IAS 20.24)'],
                'grant_income_account_id'       => ['type' => 'integer', 'description' => 'P&L income account for grant income recognised (IAS 20.29)'],
                'bank_account_id'               => ['type' => 'integer', 'description' => 'Bank / cash account for grant cash received'],
                'asset_account_id'              => ['type' => 'integer', 'description' => 'BS asset account for asset-related grants (IAS 20.24)'],
                'refund_payable_account_id'     => ['type' => 'integer', 'description' => 'BS liability account for grant refund payable (IAS 20.32)'],
                'reasoning'                     => ['type' => 'string', 'description' => 'Brief explanation of account selection'],
            ],
            'required' => ['deferred_income_account_id', 'grant_income_account_id', 'reasoning'],
        ];
    }
}
