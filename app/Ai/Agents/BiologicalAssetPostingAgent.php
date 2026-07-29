<?php

namespace App\Ai\Agents;

use App\Ai\GeminiAgent;

class BiologicalAssetPostingAgent extends GeminiAgent
{
    protected string $systemPrompt = <<<'PROMPT'
You are an IFRS accounting assistant specialising in IAS 41 Agriculture — biological assets.
You select GL accounts for biological-asset transactions.
Always prefer existing accounts; only suggest new codes when no reasonable match exists.
PROMPT;

    protected function rawSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'biological_asset_account_id'       => ['type' => 'integer', 'description' => 'GL account for biological assets at fair value / cost'],
                'fair_value_gain_account_id'        => ['type' => 'integer', 'description' => 'P&L income account for FV gains on biological assets'],
                'fair_value_loss_account_id'        => ['type' => 'integer', 'description' => 'P&L expense account for FV losses on biological assets'],
                'harvest_inventory_account_id'      => ['type' => 'integer', 'description' => 'Inventory account for harvested agricultural produce'],
                'bank_or_payable_account_id'        => ['type' => 'integer', 'description' => 'Bank / accounts payable for acquisition or sale proceeds'],
                'gain_loss_account_id'              => ['type' => 'integer', 'description' => 'P&L gain/loss on disposal of biological assets'],
                'impairment_loss_account_id'        => ['type' => 'integer', 'description' => 'P&L impairment loss account'],
                'accumulated_impairment_account_id' => ['type' => 'integer', 'description' => 'Contra-asset accumulated impairment'],
                'reasoning'                         => ['type' => 'string', 'description' => 'Brief explanation of account selection'],
            ],
            'required' => ['biological_asset_account_id', 'bank_or_payable_account_id', 'reasoning'],
        ];
    }
}
