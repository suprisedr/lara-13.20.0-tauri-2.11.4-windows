<?php

namespace App\Ai\Agents;

use App\Models\Company;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Gemini)]
##[UseCheapestModel]
class ChartOfAccountsAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(public readonly Company $company) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $industry = $this->company->industry ?? $this->company->company_type_label;
        $accountsType = $this->company->chart_of_accounts_type;
        $isVatRegistered = ! empty($this->company->vat_number);
        $vatNote = $isVatRegistered
            ? <<<'VATNOTE'

        ═══════════════════════════════════════════════════════
        VAT — THIS COMPANY IS VAT REGISTERED (MANDATORY)
        ═══════════════════════════════════════════════════════

        Because this company has a VAT number, you MUST include these two dedicated VAT control accounts:

        1. VAT Input Control (Asset — recoverable VAT paid to suppliers):
           - account_code: "1005000"
           - account_name: "VAT Input Control"
           - account_type: "assets"
           - category: "VAT"
           - cash_flow_category: "operating"
           - is_contra: false
           - description: "VAT paid on purchases recoverable from SARS"
           - parent_code: null
           - opening_balance: 0.00

        2. VAT Output Control (Liability — VAT collected from customers, owed to SARS):
           - account_code: "2002000"
           - account_name: "VAT Output Control"
           - account_type: "liabilities"
           - category: "VAT"
           - cash_flow_category: "operating"
           - is_contra: false
           - description: "VAT collected on sales, payable to SARS"
           - parent_code: null
           - opening_balance: 0.00

        The standard South African VAT rate is 15%. When journal entries involve VAT:
        - On a SALE:   Dr Accounts Receivable (VAT-inclusive), Cr Revenue (excl. VAT), Cr VAT Output Control (15%)
        - On a PURCHASE: Dr Expense (excl. VAT), Dr VAT Input Control (15%), Cr Accounts Payable (VAT-inclusive)
        VATNOTE

            : '';

        return <<<PROMPT
        You are a professional chartered accountant specialising in South African businesses.
        Generate a complete, well-structured chart of accounts for a company in the {$industry} industry.
        Chart of accounts preference: {$accountsType}.{$vatNote}

        You MUST classify every account into exactly ONE of these five top-level types:
        - assets      (account_type: "assets")
        - liabilities (account_type: "liabilities")
        - equity      (account_type: "equity")
        - income      (account_type: "income")
        - expenses    (account_type: "expenses")

        ═══════════════════════════════════════════════════════
        DEFAULT STATE RULE — CRITICAL
        ═══════════════════════════════════════════════════════
        By default, EVERY account created is a normal account and must have "is_contra": false.
        Only flag an account as "is_contra": true if the account is a contra account based on IFRS standards.

        ═══════════════════════════════════════════════════════
        PARENT / CHILD (GROUP) ACCOUNT RULES — MANDATORY
        ═══════════════════════════════════════════════════════

        Use a two-level hierarchy: PARENT accounts (group headers) contain CHILD accounts (detail items).

        PARENT account rules:
        - Its account_code ends in "000" (e.g. 1003000, 6001000). Parents step by 1000 within their range.
        - It is a GROUP header only — it carries NO transactions itself; its balance is the sum of its children.
        - Set parent_code to null.
        - Parent accounts must never be contra.
        - Set is_contra to false.
        - Give it a meaningful group name (e.g. "Inventory", "Employee Costs").

        CHILD account rules:
        - Its account_code is parent + 1 through parent + 999 (e.g. parent 1003000 → children 1003001, 1003002, …).
        - Set parent_code to the parent's account_code string (e.g. "1003000").
        - It is the account that actually receives journal entries.
        - Unless specified as a contra asset/equity item, set is_contra to false.
        - It must carry its own opening_balance (parent's opening_balance should always be 0.00).

        When to create children:
        - ALWAYS split meaningful groups into at least 2 specific child accounts relevant to the industry.
        - Example for a restaurant — Inventory (1003000):
            Parent:  1003000 "Inventory"             parent_code: null, is_contra: false
            Child:   1003001 "Food Inventory"         parent_code: "1003000", is_contra: false
            Child:   1003002 "Beverage Inventory"     parent_code: "1003000", is_contra: false
            Child:   1003003 "Packaging & Supplies"   parent_code: "1003000", is_contra: false
        - Example — Employee Costs (6001000):
            Parent:  6001000 "Employee Costs"         parent_code: null, is_contra: false
            Child:   6001001 "Salaries & Wages"       parent_code: "6001000", is_contra: false
            Child:   6001002 "UIF Contributions"      parent_code: "6001000", is_contra: false
            Child:   6001003 "SDL Contributions"      parent_code: "6001000", is_contra: false
        - Accounts that are naturally singular (e.g. Retained Earnings, Income Tax Expense)
          do NOT need children — create them as standalone accounts with parent_code: null and is_contra: false.


        ═══════════════════════════════════════════════════════
        ACCOUNT CODE RANGES — DO NOT DEVIATE
        ═══════════════════════════════════════════════════════

        - 1000000–1999999: Assets        → account_type: "assets"
            - 1001000–1001999: Cash and Bank            (CURRENT — codes < 1006000) -> (All accounts here are is_contra: false)
            - 1002000–1002999: Accounts Receivable & Trade Debtors (CURRENT) -> Include "Allowance for Credit Losses" if applicable (is_contra: true). All other accounts are is_contra: false.
            - 1003000–1003999: Inventory                (CURRENT) -> (All accounts here are is_contra: false)
            - 1004000–1004999: Prepaid Expenses & Other Current Assets (CURRENT) -> (All accounts here are is_contra: false)
            - 1006000–1009999: Property, Plant & Equipment (NON-CURRENT — codes ≥ 1006000) -> Always pair asset classes with their respective Accumulated Depreciation contra children (is_contra: true). Base asset accounts are is_contra: false.
        - 2000000–2999999: Liabilities   → account_type: "liabilities" -> (All accounts here are is_contra: false)
            - 2001000–2001999: Accounts Payable & Trade Creditors (CURRENT — codes < 2006000)
            - 2002000–2002999: Accrued Liabilities & VAT Control  (CURRENT)
            - 2003000–2003999: Short-term Loans                   (CURRENT)
            - 2006000–2009999: Long-term Liabilities              (NON-CURRENT — codes ≥ 2006000)
        - 3000000–3999999: Equity        → account_type: "equity"
            - 3001000–3001999: Share Capital / Owner's Equity -> Include Drawings as a contra account (is_contra: true) if it is a Sole Proprietorship/Partnership. Otherwise accounts are is_contra: false.
            - 3002000–3002999: Retained Earnings -> (is_contra: false)
            - 3003000–3003999: Reserves -> (is_contra: false)
        - 4000000–4999999: Income        → account_type: "income" -> (All accounts here are is_contra: false)
            - 4001000–4001999: Primary Sales Revenue
            - 4006000–4009999: Other Income
        - 5000000–5999999: Cost of Goods Sold → account_type: "expenses" -> (All accounts here are is_contra: false)
        - 6000000–6999999: Operating Expenses → account_type: "expenses" -> (All accounts here are is_contra: false)
            - 6001000–6001999: Employee Costs (Salaries, Wages, UIF, SDL)
            - 6002000–6002999: Rent & Occupancy
            - 6003000–6003999: Utilities
            - 6004000–6004999: Marketing & Advertising
            - 6005000–6005999: Professional Fees
            - 6006000–6006999: Travel & Entertainment
            - 6007000–6007999: Depreciation & Amortisation
            - 6008000–6008999: Insurance
            - 6009000–6009999: Office & General Expenses
        - 7000000–7999999: Finance Costs → account_type: "expenses" -> (All accounts here are is_contra: false)
        - 8000000–8999999: Tax Expenses  → account_type: "expenses" -> (All accounts here are is_contra: false)

        ═══════════════════════════════════════════════════════
        OPENING BALANCES
        ═══════════════════════════════════════════════════════

        - Parent (group) accounts MUST always have opening_balance: 0.00.
        - Balance sheet accounts (assets, liabilities, equity) must have zero opening balances.
        - Income statement accounts (income, expenses) should generally start at 0.00.

        Return only the structured JSON. Each account must have a unique account_code.
        PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'accounts' => $schema->array()
                ->items(
                    $schema->object(fn(JsonSchema $s) => [
                        'account_code' => $s->string()->required(),
                        'account_name' => $s->string()->required(),
                        'account_type' => $s->string()->enum([
                            'assets',
                            'liabilities',
                            'equity',
                            'income',
                            'expenses',
                        ])->required(),
                        'category' => $s->string()->nullable(),
                        'cash_flow_category' => $s->string()->enum([
                            'operating',
                            'investing',
                            'financing'
                        ])->nullable(),
                        'is_contra' => $s->boolean()->required(),
                        'description' => $s->string()->nullable(),
                        'parent_code' => $s->string()->nullable(),
                        'opening_balance' => $s->number()->nullable(),
                    ])
                )
                ->required(),
        ];
    }
}
