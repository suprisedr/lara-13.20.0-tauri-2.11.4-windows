<?php

namespace App\Ai\Agents;

use App\Ai\Concerns\HasProviderFallback;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class AssetPostingAgent implements Agent, HasStructuredOutput
{
    use Promptable, HasProviderFallback;

    public function instructions(): string
    {
        return <<<'TEXT'
        You are an IFRS-trained accountant. Given details of a PPE asset and a
        list of the company's chart of accounts (id, code, name, type), pick
        the IFRS-appropriate accounts for posting either an acquisition,
        disposal, or monthly depreciation entry.

        Roles:
        - cost_account_id: the non-current PPE-cost account that holds the
          asset at cost (codes 1500–1999, account_type "assets").
        - accumulated_depreciation_account_id: the contra-asset account that
          accumulates depreciation for this class (codes 1500–1999, contra).
        - depreciation_expense_account_id: the operating expense account for
          depreciation (codes 6600–6699, account_type "expenses").
        - bank_or_payable_account_id: the bank or payable account to credit
          on the outflow side. Required for acquisition and subsequent_cost
          (capitalisation) postings — use a bank/cash account if cash was paid,
          or a creditors/payables account if bought on credit.
        - proceeds_account_id: the bank/cash account being debited with
          disposal proceeds. Only required for disposals.
        - gain_loss_account_id: the income or expense account that the
          gain/loss on disposal posts to (income 4500–4999 for gains, expense
          6800–6899 for losses). Only required for disposals.
        - revaluation_surplus_account_id: the OCI equity account for IAS 16
          revaluation movements (codes 3800–3899, account_type "equity").
          Required for revaluation postings.
        - impairment_loss_account_id: the P&L expense for IAS 36 impairment
          losses (codes 6700–6799, account_type "expenses"). Required for
          impairment and downward revaluation postings.
        - impairment_reversal_account_id: the income account for reversing
          prior impairment (codes 4800–4899, account_type "income"). Required
          for impairment reversal postings.
        - deferred_tax_liability_account_id: the non-current liability account
          for IAS 12 deferred tax (codes 2800–2899, account_type "liabilities").
          Required when a revaluation surplus creates a taxable temporary
          difference, or when SARS wear & tear differs from accounting useful life.
        - deferred_tax_expense_account_id: the tax expense account for
          deferred tax movements (codes 7800–7899, account_type "expenses").
          Required alongside the DTL account above.

        Return null for any role that is not applicable to this posting type or
        is not present in the chart of accounts. Briefly explain your reasoning.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cost_account_id' => $schema->integer()->nullable(),
            'accumulated_depreciation_account_id' => $schema->integer()->nullable(),
            'depreciation_expense_account_id' => $schema->integer()->nullable(),
            'bank_or_payable_account_id' => $schema->integer()->nullable(),
            'proceeds_account_id' => $schema->integer()->nullable(),
            'gain_loss_account_id' => $schema->integer()->nullable(),
            'revaluation_surplus_account_id' => $schema->integer()->nullable(),
            'impairment_loss_account_id' => $schema->integer()->nullable(),
            'impairment_reversal_account_id' => $schema->integer()->nullable(),
            'deferred_tax_liability_account_id' => $schema->integer()->nullable(),
            'deferred_tax_expense_account_id' => $schema->integer()->nullable(),
            'reasoning' => $schema->string()->nullable(),
        ];
    }
}
