<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Gemini)]
class InvestmentPropertyPostingAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'TEXT'
        You are an IFRS-trained accountant. Given details of an investment
        property (IAS 40) and a list of the company's chart of accounts
        (id, code, name, type), pick the IFRS-appropriate accounts for posting
        the entry.

        Roles:
        - cost_account_id: the non-current investment property cost account
          (codes 1400–1499, account_type "assets").
        - accumulated_depreciation_account_id: the contra-asset account for
          accumulated depreciation on cost-model properties (codes 1490–1499,
          contra). Not applicable for fair value model properties.
        - depreciation_expense_account_id: the expense account for depreciation
          on cost-model properties (codes 6600–6699, account_type "expenses").
        - bank_or_payable_account_id: the bank or payable account for the
          outflow. Required for acquisition and subsequent_cost postings.
        - proceeds_account_id: the bank/cash account debited with disposal
          proceeds. Only required for disposals.
        - gain_loss_account_id: the income or expense account for disposal
          gain/loss (income 4500–4999 for gains, expense 6800–6899 for losses).
        - fair_value_gain_account_id: the income account for fair value gains
          recognised in P&L (codes 4400–4499, account_type "income"). Required
          for fair_value_adjustment postings with a gain.
        - fair_value_loss_account_id: the expense account for fair value losses
          recognised in P&L (codes 6850–6899, account_type "expenses"). Required
          for fair_value_adjustment postings with a loss.
        - impairment_loss_account_id: the P&L expense for IAS 36 impairment
          losses (codes 6700–6799, account_type "expenses").
        - impairment_reversal_account_id: the income account for reversing
          prior impairment (codes 4800–4899, account_type "income").
        - accumulated_impairment_account_id: the contra-asset account for
          accumulated impairment (codes 1490–1499, contra).

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
            'fair_value_gain_account_id' => $schema->integer()->nullable(),
            'fair_value_loss_account_id' => $schema->integer()->nullable(),
            'impairment_loss_account_id' => $schema->integer()->nullable(),
            'impairment_reversal_account_id' => $schema->integer()->nullable(),
            'accumulated_impairment_account_id' => $schema->integer()->nullable(),
            'reasoning' => $schema->string()->nullable(),
        ];
    }
}
