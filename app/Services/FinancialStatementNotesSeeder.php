<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FinancialStatementNote;

/**
 * Idempotently seeds the standard IFRS for SMEs notes for a company, modelled on
 * the IFRS Foundation's Module 8 (Notes to the Financial Statements) example.
 *
 * Existing notes (matched by slug) keep their user-edited title/body — only the
 * canonical ordering (sort_order / note_number) is kept in step, and any notes
 * missing from a company are inserted.
 */
class FinancialStatementNotesSeeder
{
    public function seed(Company $company): void
    {
        foreach (self::defaults() as $i => $note) {
            $model = FinancialStatementNote::firstOrCreate(
                ['company_id' => $company->id, 'slug' => $note['slug']],
                [
                    'note_number' => $i + 1,
                    'title'       => $note['title'],
                    'body'        => $note['body'],
                    'kind'        => $note['kind'] ?? FinancialStatementNote::KIND_TEXT,
                    'sort_order'  => $i + 1,
                    'is_active'   => true,
                ],
            );

            // Keep the canonical sequence and kind for every company (so improvements
            // apply to existing companies too) without overwriting title/body.
            $expectedKind = $note['kind'] ?? FinancialStatementNote::KIND_TEXT;
            $updates = [];
            if ((int) $model->sort_order !== $i + 1 || (int) $model->note_number !== $i + 1) {
                $updates['sort_order']  = $i + 1;
                $updates['note_number'] = $i + 1;
            }
            if ($model->kind !== $expectedKind) {
                $updates['kind'] = $expectedKind;
            }
            if (! empty($updates)) {
                $model->update($updates);
            }
        }
    }

    /**
     * @return array<int, array{slug: string, title: string, body: string, kind?: string}>
     */
    public static function defaults(): array
    {
        return [
            [
                'slug'  => 'general-information',
                'title' => 'General Information',
                'body'  => "The company is a limited liability company incorporated and domiciled in the Republic of South Africa. The principal activity of the company is [DESCRIBE PRINCIPAL ACTIVITY]. The address of its registered office and principal place of business is [REGISTERED OFFICE ADDRESS].\n\nThese financial statements are presented for the company [and its subsidiaries (together, the \"group\")] for the year ended [REPORTING DATE].",
            ],
            [
                'slug'  => 'basis-of-preparation',
                'title' => 'Basis of Preparation',
                'body'  => "These financial statements have been prepared in accordance with the International Financial Reporting Standard for Small and Medium-sized Entities (IFRS for SMEs) issued by the International Accounting Standards Board, and in the manner required by the Companies Act of South Africa.\n\nThe financial statements are presented in South African Rand (ZAR), which is the functional and presentation currency of the company, and are rounded to the nearest Rand. They have been prepared on the historical cost basis, except for items that IFRS for SMEs requires to be measured at fair value, and on the going-concern basis.\n\nThe preparation of financial statements in conformity with IFRS for SMEs requires the use of certain critical accounting estimates and requires management to exercise judgement in applying the company's accounting policies (see the note on critical accounting estimates and judgements). The principal accounting policies applied are set out in the accounting policies note.",
            ],
            [
                'slug'  => 'accounting-policies',
                'title' => 'Summary of Significant Accounting Policies',
                'body'  => "The principal accounting policies applied in the preparation of these financial statements are set out below. These policies have been applied consistently to all the years presented, unless otherwise stated.\n\nRevenue recognition\nRevenue is measured at the fair value of the consideration received or receivable for goods sold and services rendered in the ordinary course of business, net of value-added tax (VAT), returns, rebates and trade discounts. Revenue from the sale of goods is recognised when the significant risks and rewards of ownership have transferred to the buyer, the amount of revenue can be measured reliably and recovery of the consideration is probable. Revenue from services is recognised in the period in which the services are rendered. Interest income is recognised using the effective interest method and royalties on an accrual basis in accordance with the substance of the relevant agreement.\n\nProperty, plant and equipment\nProperty, plant and equipment are measured at cost less accumulated depreciation and any accumulated impairment losses. Depreciation is charged on a straight-line basis (or another method that reflects the pattern of consumption) over the estimated useful lives of the assets to their residual values. Useful lives, residual values and depreciation methods are reviewed at each reporting date and changes accounted for prospectively.\n\nIntangible assets\nIntangible assets are measured at cost less accumulated amortisation and any accumulated impairment losses, and are amortised on a straight-line basis over their estimated useful lives.\n\nInventories\nInventories are measured at the lower of cost and estimated selling price less costs to complete and sell. Cost is determined on the first-in, first-out (FIFO) basis and includes all costs of purchase, conversion and other costs incurred in bringing the inventories to their present location and condition.\n\nFinancial instruments\nBasic financial assets (including trade and other receivables and cash) and basic financial liabilities (including trade and other payables and borrowings) are initially recognised at the transaction price and subsequently measured at amortised cost using the effective interest method. Trade receivables are stated net of an allowance for expected credit losses.\n\nImpairment\nAt each reporting date the carrying amounts of assets are reviewed to determine whether there is any indication of impairment. If any such indication exists, the recoverable amount of the asset is estimated and an impairment loss recognised in profit or loss to the extent that the carrying amount exceeds the recoverable amount.\n\nLeases\nLeases that transfer substantially all the risks and rewards of ownership are classified as finance leases; all other leases are operating leases. Assets held under finance leases are recognised as assets at the lower of fair value and the present value of the minimum lease payments, with a corresponding liability. Operating lease payments are recognised as an expense on a straight-line basis over the lease term.\n\nTaxation\nThe tax expense comprises current and deferred tax. Current tax is the expected tax payable on taxable income for the year using tax rates enacted or substantively enacted at the reporting date. Deferred tax is recognised on temporary differences between the carrying amounts of assets and liabilities and their tax bases.\n\nProvisions\nProvisions are recognised when the company has a present legal or constructive obligation as a result of a past event, it is probable that an outflow of resources will be required and the amount can be estimated reliably.\n\nEmployee benefits\nThe cost of short-term employee benefits is recognised in the period in which the service is rendered. Obligations for contributions to defined-contribution plans are recognised as an expense as the related service is rendered.",
            ],
            [
                'slug'  => 'critical-estimates-and-judgements',
                'title' => 'Critical Accounting Estimates and Judgements',
                'body'  => "In preparing these financial statements management has made judgements, estimates and assumptions that affect the application of accounting policies and the reported amounts of assets, liabilities, income and expenses. Actual results may differ from these estimates. Estimates and underlying assumptions are reviewed on an ongoing basis.\n\nThe key sources of estimation uncertainty that have a significant risk of resulting in a material adjustment within the next financial year relate to:\n• the estimated useful lives, residual values and depreciation methods of property, plant and equipment;\n• the allowance for expected credit losses on trade receivables;\n• provisions, including warranty obligations, where the timing and amount of the outflow is uncertain; and\n• the recoverability of deferred tax assets, which depends on the availability of future taxable profits.",
            ],
            [
                'slug'  => 'revenue',
                'title' => 'Revenue',
                'body'  => "Revenue comprises the fair value of the consideration received or receivable for goods sold and services rendered in the ordinary course of the company's activities, net of value-added tax (VAT), returns, rebates and discounts.\n\nAn analysis of revenue by major category (for example, sale of goods, rendering of services and royalties) is presented on the face of the statement of profit or loss or below.",
            ],
            [
                'slug'  => 'other-income',
                'title' => 'Other Income',
                'body'  => "Other income comprises income earned outside the ordinary trading activities of the company, such as dividends received from investments, gains on the disposal of property, plant and equipment, sundry recoveries and foreign exchange gains. Each material category of other income is disclosed separately.",
            ],
            [
                'slug'  => 'operating-profit',
                'title' => 'Operating Profit',
                'body'  => "Operating profit is the profit for the year before finance income, finance costs and taxation. It is stated after taking into account the items disclosed in the note on profit before taxation.",
            ],
            [
                'slug'  => 'finance-income-and-costs',
                'title' => 'Finance Income and Finance Costs',
                'body'  => "Finance income comprises interest income on funds invested, recognised using the effective interest method.\n\nFinance costs comprise interest expense on bank overdrafts, loans and finance leases, recognised in profit or loss using the effective interest method. An analysis of finance costs by source is presented below.",
            ],
            [
                'slug'  => 'profit-before-taxation',
                'title' => 'Profit Before Taxation',
                'body'  => "Profit before taxation is stated after charging (crediting) the following significant items:\n• cost of inventories recognised as an expense;\n• depreciation of property, plant and equipment and amortisation of intangible assets;\n• employee benefit (staff) costs;\n• research and development costs;\n• operating lease charges;\n• impairment losses and warranty expense; and\n• foreign exchange gains and losses.",
            ],
            [
                'slug'  => 'taxation',
                'title' => 'Income Tax Expense',
                'body'  => "The income tax expense for the year comprises current tax and deferred tax. Tax is recognised in profit or loss except to the extent that it relates to items recognised in other comprehensive income or directly in equity.\n\nCurrent tax is the expected tax payable on the taxable income for the year, calculated using the tax rates enacted or substantively enacted at the reporting date, together with any adjustment to tax payable in respect of prior years.\n\nA reconciliation between the income tax expense and the accounting profit multiplied by the applicable South African corporate income tax rate, explaining material reconciling items such as non-deductible expenses and exempt income, is presented below.",
            ],
            [
                'slug'  => 'deferred-tax',
                'title' => 'Deferred Tax',
                'body'  => "Deferred tax is recognised in respect of temporary differences between the carrying amounts of assets and liabilities for financial reporting purposes and the amounts used for taxation purposes. Deferred tax is measured at the tax rates that are expected to apply when the temporary differences reverse, based on rates enacted or substantively enacted at the reporting date.\n\nA deferred tax asset is recognised only to the extent that it is probable that future taxable profits will be available against which the deductible temporary difference can be utilised. Deferred tax assets and liabilities are offset when there is a legally enforceable right to set off and they relate to income taxes levied by the same tax authority. An analysis of the deferred tax balance by type of temporary difference is presented below.",
            ],
            [
                'slug'  => 'property-plant-equipment',
                'title' => 'Property, Plant and Equipment',
                'body'  => "Property, plant and equipment are measured at cost less accumulated depreciation and any accumulated impairment losses. Depreciation is calculated on the straight-line method to write off the cost of each asset to its residual value over its estimated useful life. Useful lives, residual values and depreciation methods are reviewed at each reporting date and the effect of any change in estimate is accounted for prospectively.\n\nWhere the carrying amount of an asset exceeds its estimated recoverable amount, an impairment loss is recognised in profit or loss. The reconciliation of the carrying amount of each class of property, plant and equipment at the beginning and end of the year — showing cost, additions, disposals, depreciation and impairment — is set out in the movement schedule below.",
                'kind'  => FinancialStatementNote::KIND_PPE,
            ],
            [
                'slug'  => 'intangible-assets',
                'title' => 'Intangible Assets',
                'body'  => "Intangible assets acquired separately are measured on initial recognition at cost. Subsequent to initial recognition, intangible assets with finite useful lives are carried at cost less accumulated amortisation and any accumulated impairment losses. Amortisation is charged on a straight-line basis over the estimated useful life of the asset and recognised in profit or loss. A reconciliation of the carrying amount at the beginning and end of the year is presented below.",
                'kind'  => FinancialStatementNote::KIND_INTANGIBLE,
            ],
            [
                'slug'  => 'investment-in-associate',
                'title' => 'Investments in Associates',
                'body'  => "An associate is an entity over which the company has significant influence and that is neither a subsidiary nor an interest in a joint venture. Investments in associates are accounted for at cost less any accumulated impairment losses [or using the equity method / fair value model, as elected]. Dividends received from associates are recognised in profit or loss as other income. Details of the company's investments in associates, and dividends received, are disclosed below.",
            ],
            [
                'slug'  => 'inventories',
                'title' => 'Inventories',
                'body'  => "Inventories are measured at the lower of cost and estimated selling price less costs to complete and sell (IAS 2.9). Cost is determined using the first-in, first-out (FIFO) method and includes all costs of purchase, costs of conversion and other costs incurred in bringing the inventories to their present location and condition.\n\nWhere the net realisable value of an inventory item falls below its cost, a write-down is recognised in profit or loss. Any subsequent reversal of a write-down (limited to the original cost) is also recognised in profit or loss (IAS 2.33–34).\n\nThe reconciliation of the carrying amount of inventories by category is set out in the movement schedule below.",
                'kind'  => FinancialStatementNote::KIND_INVENTORY,
            ],
            [
                'slug'  => 'trade-and-other-receivables',
                'title' => 'Trade and Other Receivables',
                'body'  => "Trade and other receivables are recognised initially at the transaction price and subsequently measured at amortised cost using the effective interest method, less an allowance for expected credit losses. An allowance is established when there is objective evidence that the company will not be able to collect all amounts due according to the original terms. An analysis of trade and other receivables, including prepayments, is presented below.",
            ],
            [
                'slug'  => 'cash-and-cash-equivalents',
                'title' => 'Cash and Cash Equivalents',
                'body'  => "Cash and cash equivalents comprise cash on hand, deposits held at call with banks and other short-term highly liquid investments with original maturities of three months or less. Bank overdrafts that are repayable on demand and form an integral part of the company's cash management are included as a component of cash and cash equivalents for the purpose of the statement of cash flows.",
            ],
            [
                'slug'  => 'share-capital',
                'title' => 'Share Capital',
                'body'  => "Ordinary shares are classified as equity. Incremental costs directly attributable to the issue of new shares are recognised in equity as a deduction, net of tax, from the proceeds. The number of shares authorised, issued and fully paid, and their par or stated value, is disclosed below.",
            ],
            [
                'slug'  => 'trade-and-other-payables',
                'title' => 'Trade and Other Payables',
                'body'  => "Trade and other payables are obligations to pay for goods or services acquired in the ordinary course of business. They are recognised initially at the transaction price and subsequently measured at amortised cost using the effective interest method. Trade payables denominated in foreign currencies are translated at the rate of exchange ruling at the reporting date.",
            ],
            [
                'slug'  => 'borrowings',
                'title' => 'Bank Overdraft and Borrowings',
                'body'  => "Borrowings are recognised initially at the transaction price net of transaction costs incurred, and are subsequently measured at amortised cost. Any difference between the proceeds and the redemption value is recognised in profit or loss over the period of the borrowings using the effective interest method.\n\nDetails of the borrowings — including security provided, interest rates and repayment terms — are disclosed below. Borrowings are classified as current liabilities unless the company has an unconditional right to defer settlement for at least twelve months after the reporting date.",
            ],
            [
                'slug'  => 'provisions',
                'title' => 'Provisions',
                'body'  => "Provisions are recognised when the company has a present legal or constructive obligation as a result of a past event, it is probable that an outflow of resources embodying economic benefits will be required to settle the obligation, and a reliable estimate can be made of the amount of the obligation. Provisions are measured at the best estimate of the expenditure required to settle the obligation at the reporting date.\n\nA provision for warranty obligations is recognised at the date of sale of the relevant products, based on historical warranty data and a weighting of possible outcomes. A reconciliation of the carrying amount of provisions at the beginning and end of the year is presented below.",
            ],
            [
                'slug'  => 'employee-benefits',
                'title' => 'Employee Benefit Obligations',
                'body'  => "Short-term employee benefits, including salaries, wages, bonuses, paid annual leave and sick leave, are recognised in profit or loss in the period in which the associated services are rendered.\n\nThe company contributes to defined-contribution retirement and other funds; contributions are recognised as an expense as employees render service. Where the company has a long-service or other long-term employee benefit obligation, it is measured at the present value of the expected future payments, and a reconciliation of the obligation and its split between current and non-current portions is presented below.",
            ],
            [
                'slug'  => 'leases',
                'title' => 'Leases',
                'body'  => "Finance leases\nAssets held under finance leases are recognised initially at the lower of fair value and the present value of the minimum lease payments, with a corresponding liability. Lease payments are apportioned between the finance charge and the reduction of the outstanding liability. The future minimum lease payments under finance leases, analysed by maturity, are presented below.\n\nOperating leases\nLeases in which a significant portion of the risks and rewards of ownership are retained by the lessor are classified as operating leases. Payments made under operating leases are charged to profit or loss on a straight-line basis over the lease term. The company's commitments under non-cancellable operating leases, analysed by maturity, are presented below.",
            ],
            [
                'slug'  => 'related-party-transactions',
                'title' => 'Related Party Transactions',
                'body'  => "Related parties of the company include its holding company, subsidiaries and fellow subsidiaries, associates, directors and other key management personnel, and shareholders with significant influence, together with close members of their families and entities controlled by them.\n\nTransactions between group companies are eliminated on consolidation. Other related party transactions are carried out on terms equivalent to those that prevail in arm's-length transactions unless otherwise stated. The nature of related party relationships, the transactions entered into, outstanding balances, and the total remuneration of directors and key management personnel are disclosed below.",
            ],
            [
                'slug'  => 'commitments-and-contingencies',
                'title' => 'Commitments and Contingent Liabilities',
                'body'  => "Capital commitments\nCapital expenditure contracted for at the reporting date but not yet recognised in the financial statements is disclosed below.\n\nContingent liabilities\nContingent liabilities are possible obligations whose existence will be confirmed only by uncertain future events, or present obligations that are not recognised because an outflow is not probable or the amount cannot be measured reliably. The nature of any contingent liabilities — for example pending litigation or guarantees issued — is disclosed below. The company has [DESCRIBE COMMITMENTS AND CONTINGENCIES, OR STATE 'NO MATERIAL COMMITMENTS OR CONTINGENT LIABILITIES'] at the reporting date.",
            ],
            [
                'slug'  => 'events-after-reporting-period',
                'title' => 'Events After the Reporting Period',
                'body'  => "Adjusting events that provide evidence of conditions that existed at the reporting date are reflected in the financial statements. Non-adjusting events that are indicative of conditions that arose after the reporting date are disclosed, where material, together with an estimate of their financial effect.\n\nThe directors are not aware of any matter or circumstance arising since the reporting date and up to the date of approval of these financial statements, other than those disclosed, that would require adjustment to, or disclosure in, these financial statements.",
            ],
            [
                'slug'  => 'approval-of-financial-statements',
                'title' => 'Approval of Financial Statements',
                'body'  => "These financial statements were approved by the board of directors and authorised for issue on [DATE OF APPROVAL].",
            ],
        ];
    }
}
