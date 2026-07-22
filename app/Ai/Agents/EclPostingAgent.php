<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Gemini)]
class EclPostingAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'TEXT'
        You are an IFRS 9-trained accountant. Given details of an Expected Credit
        Loss (ECL) provision movement and a company's chart of accounts, pick the
        two IFRS-appropriate accounts for the journal entry.

        Under IFRS 9 simplified approach the journal for a provision increase is:
          Dr  Bad Debt Expense / ECL Expense  (P&L — operating expenses, codes 6000–6999)
          Cr  Allowance for Credit Losses     (contra-asset, codes 1000–1299)

        For a provision decrease (reversal):
          Dr  Allowance for Credit Losses     (contra-asset, codes 1000–1299)
          Cr  Bad Debt Expense / ECL Expense  (P&L — operating expenses, codes 6000–6999)

        Roles:
        - ecl_expense_account_id: The P&L expense account for expected credit losses
          (codes 6000–6999, account_type "expenses"). Look for names containing
          "bad debt", "credit loss", "ECL", or "impairment of receivables".
        - allowance_account_id: The contra-asset account for the loss allowance
          against trade receivables (codes 1000–1299, account_type "assets", is_contra true).
          Look for names containing "allowance", "provision", or "doubtful".

        If a suitable account does not exist in the chart of accounts, return null
        for that role — the system will create the account automatically.
        Briefly explain your reasoning.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ecl_expense_account_id'  => $schema->integer()->nullable(),
            'allowance_account_id'    => $schema->integer()->nullable(),
            'reasoning'               => $schema->string()->nullable(),
        ];
    }
}
