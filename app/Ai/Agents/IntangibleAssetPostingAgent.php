<?php

namespace App\Ai\Agents;

use App\Ai\Concerns\HasProviderFallback;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class IntangibleAssetPostingAgent implements Agent, HasStructuredOutput
{
    use Promptable, HasProviderFallback;

    public function instructions(): string
    {
        return <<<'TEXT'
        You are an IFRS-trained accountant. Given details of an intangible asset
        (IAS 38) and a list of the company's chart of accounts (id, code, name,
        type), pick the IFRS-appropriate accounts for an acquisition, disposal,
        amortisation, revaluation, impairment, impairment reversal, or
        subsequent-cost capitalisation posting.

        Account roles (use the company's existing codes where they exist):
        - cost_account_id: intangible-asset cost account (codes 1700–1789,
          account_type "assets").
        - accumulated_amortisation_account_id: contra-asset accumulating
          amortisation (codes 1790–1798, contra).
        - amortisation_expense_account_id: operating expense for amortisation
          (codes 6650–6699, account_type "expenses").
        - accumulated_impairment_account_id: contra-asset for impairment
          accumulation (codes 1780–1789, contra).
        - impairment_loss_account_id: P&L impairment loss (codes 6750–6799,
          account_type "expenses").
        - impairment_reversal_account_id: P&L impairment reversal income (codes
          4850–4899, account_type "income"). NOTE: goodwill impairment reversal
          is PROHIBITED (IAS 36.124).
        - revaluation_surplus_account_id: OCI revaluation surplus (codes
          3850–3899, account_type "equity"). Only used when the intangible class
          uses the revaluation model and an active market exists (IAS 38.75).
        - bank_or_payable_account_id: bank or payable credited on acquisition
          or subsequent cost.
        - proceeds_account_id: bank/cash account debited with disposal proceeds.
        - gain_loss_account_id: income (4950–4999) for gains or expense
          (6895–6899) for losses on disposal.

        Return null for any role that is not present in the chart of accounts.
        The calling code will create missing accounts on the IFRS-correct code
        and then re-post. Briefly explain your reasoning.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cost_account_id'                     => $schema->integer()->nullable(),
            'accumulated_amortisation_account_id' => $schema->integer()->nullable(),
            'amortisation_expense_account_id'     => $schema->integer()->nullable(),
            'accumulated_impairment_account_id'   => $schema->integer()->nullable(),
            'impairment_loss_account_id'          => $schema->integer()->nullable(),
            'impairment_reversal_account_id'      => $schema->integer()->nullable(),
            'revaluation_surplus_account_id'      => $schema->integer()->nullable(),
            'bank_or_payable_account_id'          => $schema->integer()->nullable(),
            'proceeds_account_id'                 => $schema->integer()->nullable(),
            'gain_loss_account_id'                => $schema->integer()->nullable(),
            'reasoning'                           => $schema->string()->nullable(),
        ];
    }
}
