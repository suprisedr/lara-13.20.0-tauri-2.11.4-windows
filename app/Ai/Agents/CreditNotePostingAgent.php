<?php

namespace App\Ai\Agents;

use App\Ai\Concerns\HasProviderFallback;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class CreditNotePostingAgent implements Agent, HasStructuredOutput
{
    use Promptable, HasProviderFallback;

    public function instructions(): string
    {
        return <<<'TEXT'
        You are an IFRS-compliant accounting AI. You must select the correct chart-of-accounts
        entries to post a credit note journal under IFRS 15 (revenue reversal) and IAS 2 (inventory
        return where applicable).

        A credit note issued to a customer reverses part or all of a sales invoice:
          Dr  Revenue (sales reversal)          — revenue_account_id
          Dr  VAT / Output Tax (if applicable)  — vat_account_id
          Cr  Accounts Receivable               — ar_account_id

        If physical goods are being returned to stock:
          Dr  Inventory                         — inventory_account_id
          Cr  Cost of Sales                     — cos_account_id

        Choose accounts from the provided chart-of-accounts list. Match by name and code.
        Return null for optional fields if not applicable. Always return reasoning.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'revenue_account_id'   => $schema->integer()->nullable(),
            'ar_account_id'        => $schema->integer()->nullable(),
            'vat_account_id'       => $schema->integer()->nullable(),
            'inventory_account_id' => $schema->integer()->nullable(),
            'cos_account_id'       => $schema->integer()->nullable(),
            'reasoning'            => $schema->string()->nullable(),
        ];
    }
}
