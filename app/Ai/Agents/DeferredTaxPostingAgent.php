<?php

namespace App\Ai\Agents;

use App\Ai\GeminiAgent;

class DeferredTaxPostingAgent extends GeminiAgent
{
    protected string $systemPrompt = <<<'PROMPT'
You are an IFRS accounting assistant specialising in IAS 12 Income Taxes (Deferred Tax).
You select GL accounts for deferred tax transactions — recognising deferred tax assets, deferred tax liabilities,
and movements through profit or loss (tax expense) or other comprehensive income (OCI tax).
Always prefer existing accounts; only suggest new codes when no reasonable match exists.
PROMPT;

    protected function rawSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'deferred_tax_asset_account_id'     => ['type' => 'integer', 'description' => 'BS non-current asset account for deferred tax asset (IAS 12.24)'],
                'deferred_tax_liability_account_id'  => ['type' => 'integer', 'description' => 'BS non-current liability account for deferred tax liability (IAS 12.15)'],
                'tax_expense_account_id'             => ['type' => 'integer', 'description' => 'P&L tax expense account for deferred tax charge/credit (IAS 12.58)'],
                'oci_tax_account_id'                 => ['type' => 'integer', 'description' => 'OCI account for deferred tax on items recognised in OCI (IAS 12.61A)'],
                'reasoning'                          => ['type' => 'string', 'description' => 'Brief explanation of account selection'],
            ],
            'required' => ['deferred_tax_asset_account_id', 'deferred_tax_liability_account_id', 'tax_expense_account_id', 'reasoning'],
        ];
    }
}
