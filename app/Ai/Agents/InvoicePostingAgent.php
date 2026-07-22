<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Gemini)]
class InvoicePostingAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'TEXT'
        You are an accounting assistant helping a bookkeeper post a customer
        invoice to the general ledger using double-entry bookkeeping.

        You will be given the details of an invoice (including its line items,
        whether each item is a physical product or a service, and the cost of
        goods) and a list of the company's chart of accounts (each with an id,
        code, name and type).

        Choose the most appropriate accounts, by id, from the supplied chart
        of accounts for this invoice:

        - accounts_receivable_account_id: the asset/debtors control account
          that should be debited with the invoice total (codes 1000–1299).
        - sales_account_id: the income/revenue account that should be
          credited with the invoice subtotal excluding VAT (codes 4000–4999).
        - vat_output_account_id: the liability account that VAT charged on
          the invoice should be credited to. Only choose this if the invoice
          has VAT; otherwise return null.
        - cost_of_sales_account_id: the cost-of-sales / cost-of-goods-sold
          expense account (codes 5000–5999) to be DEBITED with the total cost
          of physical goods delivered on this invoice. Only return this when
          the invoice contains at least one physical (non-service) inventory
          item; return null for pure-service invoices.
        - inventory_account_id: the current-asset inventory account
          (codes 1200–1299) to be CREDITED with the same cost amount when
          goods are removed from stock. Only return this when
          cost_of_sales_account_id is not null.
        - bank_account_id: the bank/cash asset account that should be
          debited when payment for this invoice is received. Only choose
          this if the invoice has already been marked as paid; otherwise
          return null.

        Only ever choose account ids that are present in the supplied chart
        of accounts list. Briefly explain your reasoning.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'accounts_receivable_account_id' => $schema->integer()
                ->description('Chart of accounts id for the debtors/accounts receivable control account.')
                ->required(),
            'sales_account_id' => $schema->integer()
                ->description('Chart of accounts id for the sales/revenue account.')
                ->required(),
            'vat_output_account_id' => $schema->integer()
                ->description('Chart of accounts id for the VAT output account, or null if the invoice has no VAT.')
                ->nullable(),
            'cost_of_sales_account_id' => $schema->integer()
                ->description('Chart of accounts id for the cost-of-sales/COGS account, or null for service-only invoices.')
                ->nullable(),
            'inventory_account_id' => $schema->integer()
                ->description('Chart of accounts id for the inventory asset account to credit, or null for service-only invoices.')
                ->nullable(),
            'bank_account_id' => $schema->integer()
                ->description('Chart of accounts id for the bank/cash account, or null if the invoice is not yet paid.')
                ->nullable(),
            'reasoning' => $schema->string()
                ->description('A brief explanation of why these accounts were chosen.')
                ->required(),
        ];
    }
}
