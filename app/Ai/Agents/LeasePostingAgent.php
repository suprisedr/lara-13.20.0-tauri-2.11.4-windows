<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Gemini)]
class LeasePostingAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'TEXT'
        You are an IFRS-trained accountant. Given details of an IFRS 16 lease
        event and a list of the company's chart of accounts (id, code, name,
        type), pick the IFRS-appropriate accounts for posting.

        LESSEE postings:
        - commencement: Dr ROU Asset / Cr Lease Liability
        - payment: Dr Lease Liability + Dr Interest Expense / Cr Bank
        - depreciation: Dr Depreciation Expense / Cr Accumulated Depreciation
        - modification: Dr/Cr ROU Asset / Dr/Cr Lease Liability
        - impairment: Dr Impairment Loss / Cr ROU Asset (Acc. Impairment)
        - reverse_impairment: Dr ROU Asset / Cr Impairment Reversal Income
        - termination: Derecognise ROU asset and lease liability, post gain/loss

        LESSOR — FINANCE LEASE postings:
        - commencement: Dr Net Investment in Lease / Cr Asset (derecognise underlying asset)
        - payment_received: Dr Bank / Cr Net Investment in Lease (capital) + Cr Finance Income (interest)
        - termination: Derecognise net investment, post gain/loss

        LESSOR — OPERATING LEASE postings:
        - commencement: No journal entry (continue recognising the asset)
        - rental_income: Dr Bank / Cr Rental Income (straight-line)
        - termination: No derecognition of asset

        Roles:
        - rou_asset_account_id: ROU asset account (lessee, codes 1300–1499).
        - lease_liability_account_id: Lease liability (lessee, codes 2300–2499).
        - accumulated_depreciation_account_id: Contra-asset for ROU depreciation (lessee).
        - depreciation_expense_account_id: Operating expense for depreciation (lessee, codes 6600–6699).
        - interest_expense_account_id: Finance cost for interest on lease liability (lessee, codes 7100–7199).
        - bank_account_id: Bank/cash account for payments received/made.
        - net_investment_account_id: Net investment in lease (lessor finance, codes 1300–1499).
        - finance_income_account_id: Finance income earned (lessor finance, codes 4200–4299).
        - rental_income_account_id: Rental/lease income (lessor operating, codes 4100–4199).
        - impairment_loss_account_id: Impairment loss expense (codes 6700–6799).
        - impairment_reversal_account_id: Impairment reversal income (codes 4800–4899).
        - gain_loss_account_id: Gain/loss on termination (income 4500–4999 or expense 6800–6899).

        Return null for any role not present in the chart of accounts or not
        required for this specific posting type. The calling code will create
        missing accounts deterministically. Briefly explain your reasoning.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'rou_asset_account_id' => $schema->integer()->nullable(),
            'lease_liability_account_id' => $schema->integer()->nullable(),
            'accumulated_depreciation_account_id' => $schema->integer()->nullable(),
            'depreciation_expense_account_id' => $schema->integer()->nullable(),
            'interest_expense_account_id' => $schema->integer()->nullable(),
            'bank_account_id' => $schema->integer()->nullable(),
            'net_investment_account_id' => $schema->integer()->nullable(),
            'finance_income_account_id' => $schema->integer()->nullable(),
            'rental_income_account_id' => $schema->integer()->nullable(),
            'impairment_loss_account_id' => $schema->integer()->nullable(),
            'impairment_reversal_account_id' => $schema->integer()->nullable(),
            'gain_loss_account_id' => $schema->integer()->nullable(),
            'reasoning' => $schema->string()->nullable(),
        ];
    }
}
