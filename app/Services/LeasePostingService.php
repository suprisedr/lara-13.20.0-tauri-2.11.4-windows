<?php

namespace App\Services;

use App\Ai\Agents\LeasePostingAgent;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Lease;
use App\Models\LeaseEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LeasePostingService
{
    private array $accountCache = [];

    public function __construct(
        private readonly TransactionService $transactions,
        private readonly AccountVectorSearchService $accountSearch,
    ) {}

    public function postCommencementWithAi(Lease $lease, User $user): void
    {
        $company = $lease->company;
        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($lease, $accounts, 'commencement');

        if ($lease->isLessee()) {
            $this->postLesseeCommencement($lease, $user, $company, $accounts, $response);
        } elseif ($lease->isFinanceLease()) {
            $this->postLessorFinanceCommencement($lease, $user, $company, $accounts, $response);
        }
    }

    public function postPaymentWithAi(Lease $lease, User $user, string $date, float $amount): void
    {
        $company = $lease->company;
        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($lease, $accounts, 'payment', [
            'payment_date' => $date,
            'payment_amount' => $amount,
        ]);

        if ($lease->isLessee()) {
            $monthlyRate = (float) $lease->incremental_borrowing_rate / 12;
            $balance = $lease->leaseLiabilityBalance($date);
            $interest = round($balance * $monthlyRate, 2);
            $capital = round(min($amount - $interest, $balance), 2);

            $rouId = $this->resolveOrCreate($company, $response['lease_liability_account_id'], 'Lease Liability', '2004000', 'liabilities');
            $interestId = $this->resolveOrCreate($company, $response['interest_expense_account_id'], 'Interest on Lease Liabilities', '7002000', 'expenses');
            $bankId = $response['bank_account_id'] ?? null;
            if (! $bankId) throw new RuntimeException('AI could not determine a bank account for lease payment.');

            $lines = [];
            if ($capital > 0) {
                $lines[] = ['chart_of_account_id' => $rouId, 'type' => 'debit', 'amount' => $capital, 'description' => 'Lease payment — capital'];
            }
            if ($interest > 0) {
                $lines[] = ['chart_of_account_id' => $interestId, 'type' => 'debit', 'amount' => $interest, 'description' => 'Lease payment — interest'];
            }
            $lines[] = ['chart_of_account_id' => $bankId, 'type' => 'credit', 'amount' => $amount, 'description' => 'Lease payment'];

            $this->record($company, $user, $lease, $date, 'Lease payment — '.$lease->name, $lines, $response);
        } elseif ($lease->isFinanceLease()) {
            $monthlyRate = (float) $lease->incremental_borrowing_rate / 12;
            $balance = $lease->netInvestmentBalance($date);
            $financeIncome = round($balance * $monthlyRate, 2);
            $capital = round(min($amount - $financeIncome, $balance), 2);

            $netInvId = $this->resolveOrCreate($company, $response['net_investment_account_id'], 'Net Investment in Lease', '1004050', 'assets');
            $finIncId = $this->resolveOrCreate($company, $response['finance_income_account_id'], 'Finance Income — Leases', '4003000', 'income');
            $bankId = $response['bank_account_id'] ?? null;
            if (! $bankId) throw new RuntimeException('AI could not determine a bank account for lease payment received.');

            $lines = [
                ['chart_of_account_id' => $bankId, 'type' => 'debit', 'amount' => $amount, 'description' => 'Lease payment received'],
            ];
            if ($capital > 0) {
                $lines[] = ['chart_of_account_id' => $netInvId, 'type' => 'credit', 'amount' => $capital, 'description' => 'Lease payment — capital repayment'];
            }
            if ($financeIncome > 0) {
                $lines[] = ['chart_of_account_id' => $finIncId, 'type' => 'credit', 'amount' => $financeIncome, 'description' => 'Finance income earned'];
            }

            $this->record($company, $user, $lease, $date, 'Lease payment received — '.$lease->name, $lines, $response);
        } elseif ($lease->isOperatingLease()) {
            $rentalId = $this->resolveOrCreate($company, $response['rental_income_account_id'], 'Rental Income — Operating Leases', '4002000', 'income');
            $bankId = $response['bank_account_id'] ?? null;
            if (! $bankId) throw new RuntimeException('AI could not determine a bank account for rental income.');

            $lines = [
                ['chart_of_account_id' => $bankId, 'type' => 'debit', 'amount' => $amount, 'description' => 'Rental income received'],
                ['chart_of_account_id' => $rentalId, 'type' => 'credit', 'amount' => $amount, 'description' => 'Operating lease rental income'],
            ];

            $this->record($company, $user, $lease, $date, 'Operating lease rental — '.$lease->name, $lines, $response);
        }
    }

    public function postModificationWithAi(Lease $lease, User $user, float $adjustmentAmount, string $date): void
    {
        if (! $lease->isLessee() || abs($adjustmentAmount) < 0.01) return;

        $company = $lease->company;
        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($lease, $accounts, 'modification', [
            'adjustment_amount' => $adjustmentAmount,
            'modification_date' => $date,
        ]);

        $rouId = $this->resolveOrCreate($company, $response['rou_asset_account_id'], 'Right-of-Use Assets', '1004050', 'assets');
        $liabId = $this->resolveOrCreate($company, $response['lease_liability_account_id'], 'Lease Liability', '2004000', 'liabilities');

        $absAmount = abs($adjustmentAmount);
        if ($adjustmentAmount > 0) {
            $lines = [
                ['chart_of_account_id' => $rouId, 'type' => 'debit', 'amount' => $absAmount, 'description' => 'Lease modification — increase ROU'],
                ['chart_of_account_id' => $liabId, 'type' => 'credit', 'amount' => $absAmount, 'description' => 'Lease modification — increase liability'],
            ];
        } else {
            $lines = [
                ['chart_of_account_id' => $liabId, 'type' => 'debit', 'amount' => $absAmount, 'description' => 'Lease modification — decrease liability'],
                ['chart_of_account_id' => $rouId, 'type' => 'credit', 'amount' => $absAmount, 'description' => 'Lease modification — decrease ROU'],
            ];
        }

        $this->record($company, $user, $lease, $date, 'Lease modification — '.$lease->name, $lines, $response);
    }

    public function postImpairmentWithAi(Lease $lease, User $user, float $amount, string $date): void
    {
        if (! $lease->isLessee()) return;

        $company = $lease->company;
        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($lease, $accounts, 'impairment', [
            'impairment_amount' => $amount,
            'impairment_date' => $date,
        ]);

        $impLossId = $this->resolveOrCreate($company, $response['impairment_loss_account_id'], 'Impairment Loss — ROU Assets', '6008000', 'expenses');
        $rouId = $this->resolveOrCreate($company, $response['rou_asset_account_id'], 'Right-of-Use Assets', '1004050', 'assets');

        $lines = [
            ['chart_of_account_id' => $impLossId, 'type' => 'debit', 'amount' => $amount, 'description' => 'ROU asset impairment'],
            ['chart_of_account_id' => $rouId, 'type' => 'credit', 'amount' => $amount, 'description' => 'ROU asset impairment'],
        ];

        $this->record($company, $user, $lease, $date, 'ROU impairment — '.$lease->name, $lines, $response);
    }

    public function postImpairmentReversalWithAi(Lease $lease, User $user, float $amount, string $date): void
    {
        if (! $lease->isLessee()) return;

        $company = $lease->company;
        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($lease, $accounts, 'reverse_impairment', [
            'reversal_amount' => $amount,
            'reversal_date' => $date,
        ]);

        $rouId = $this->resolveOrCreate($company, $response['rou_asset_account_id'], 'Right-of-Use Assets', '1004050', 'assets');
        $revId = $this->resolveOrCreate($company, $response['impairment_reversal_account_id'], 'Impairment Reversal — ROU Assets', '4009000', 'income');

        $lines = [
            ['chart_of_account_id' => $rouId, 'type' => 'debit', 'amount' => $amount, 'description' => 'ROU impairment reversal'],
            ['chart_of_account_id' => $revId, 'type' => 'credit', 'amount' => $amount, 'description' => 'ROU impairment reversal income'],
        ];

        $this->record($company, $user, $lease, $date, 'ROU impairment reversal — '.$lease->name, $lines, $response);
    }

    public function postTerminationWithAi(Lease $lease, User $user, string $date, float $gainLoss): void
    {
        $company = $lease->company;
        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($lease, $accounts, 'termination', [
            'termination_date' => $date,
            'gain_loss' => $gainLoss,
        ]);

        $lines = [];

        if ($lease->isLessee()) {
            $rouId = $this->resolveOrCreate($company, $response['rou_asset_account_id'], 'Right-of-Use Assets', '1004050', 'assets');
            $accDepId = $this->resolveOrCreate($company, $response['accumulated_depreciation_account_id'], 'Accumulated Depreciation — ROU', '1004099', 'assets', true);
            $liabId = $this->resolveOrCreate($company, $response['lease_liability_account_id'], 'Lease Liability', '2004000', 'liabilities');

            $rouCost = (float) $lease->rou_asset_cost;
            $accDep = $lease->accumulatedDepreciation($date);
            $accImp = (float) ($lease->accumulated_impairment ?? 0);
            $liabBal = $lease->leaseLiabilityBalance($date);

            $lines[] = ['chart_of_account_id' => $liabId, 'type' => 'debit', 'amount' => $liabBal, 'description' => 'Derecognise lease liability'];
            if ($accDep > 0) {
                $lines[] = ['chart_of_account_id' => $accDepId, 'type' => 'debit', 'amount' => $accDep, 'description' => 'Derecognise accumulated depreciation'];
            }
            if ($accImp > 0) {
                $accImpId = $this->resolveOrCreate($company, $response['accumulated_impairment_account_id'] ?? null, 'Accumulated Impairment — ROU Assets', '1004098', 'assets', true);
                $lines[] = ['chart_of_account_id' => $accImpId, 'type' => 'debit', 'amount' => $accImp, 'description' => 'Derecognise accumulated impairment'];
            }
            $lines[] = ['chart_of_account_id' => $rouId, 'type' => 'credit', 'amount' => $rouCost, 'description' => 'Derecognise ROU asset'];

            if (abs($gainLoss) >= 0.01) {
                $glId = $this->resolveOrCreate($company, $response['gain_loss_account_id'],
                    $gainLoss > 0 ? 'Gain on Lease Termination' : 'Loss on Lease Termination',
                    $gainLoss > 0 ? '4010000' : '6009090',
                    $gainLoss > 0 ? 'income' : 'expenses'
                );
                if ($gainLoss > 0) {
                    $lines[] = ['chart_of_account_id' => $glId, 'type' => 'credit', 'amount' => abs($gainLoss), 'description' => 'Gain on lease termination'];
                } else {
                    $lines[] = ['chart_of_account_id' => $glId, 'type' => 'debit', 'amount' => abs($gainLoss), 'description' => 'Loss on lease termination'];
                }
            }
        } elseif ($lease->isFinanceLease()) {
            $netInvId = $this->resolveOrCreate($company, $response['net_investment_account_id'], 'Net Investment in Lease', '1004050', 'assets');
            $netInvBal = $lease->netInvestmentBalance($date);

            if ($netInvBal > 0) {
                $lines[] = ['chart_of_account_id' => $netInvId, 'type' => 'credit', 'amount' => $netInvBal, 'description' => 'Derecognise net investment in lease'];

                if (abs($gainLoss) >= 0.01) {
                    $glId = $this->resolveOrCreate($company, $response['gain_loss_account_id'],
                        $gainLoss > 0 ? 'Gain on Lease Termination' : 'Loss on Lease Termination',
                        $gainLoss > 0 ? '4010000' : '6009090',
                        $gainLoss > 0 ? 'income' : 'expenses'
                    );
                    if ($gainLoss > 0) {
                        $lines[] = ['chart_of_account_id' => $glId, 'type' => 'credit', 'amount' => abs($gainLoss), 'description' => 'Gain on lease termination'];
                    } else {
                        $lines[] = ['chart_of_account_id' => $glId, 'type' => 'debit', 'amount' => abs($gainLoss), 'description' => 'Loss on lease termination'];
                    }

                    $bankId = $response['bank_account_id'] ?? null;
                    if ($bankId) {
                        $lines[] = ['chart_of_account_id' => $bankId, 'type' => 'debit', 'amount' => $netInvBal + ($gainLoss > 0 ? -abs($gainLoss) : abs($gainLoss)), 'description' => 'Settlement received'];
                    }
                }
            }
        }

        if (! empty($lines)) {
            $this->record($company, $user, $lease, $date, 'Lease termination — '.$lease->name, $lines, $response);
        }
    }

    private function postLesseeCommencement(Lease $lease, User $user, Company $company, $accounts, array $response): void
    {
        $rouId = $this->resolveOrCreate($company, $response['rou_asset_account_id'], 'Right-of-Use Assets', '1004050', 'assets');
        $liabId = $this->resolveOrCreate($company, $response['lease_liability_account_id'], 'Lease Liability', '2004000', 'liabilities');

        $rouCost = (float) $lease->rou_asset_cost;
        $liability = (float) $lease->lease_liability_opening;
        $idc = (float) ($lease->initial_direct_costs ?? 0);

        $lines = [
            ['chart_of_account_id' => $rouId, 'type' => 'debit', 'amount' => $rouCost, 'description' => 'ROU asset recognised at commencement'],
            ['chart_of_account_id' => $liabId, 'type' => 'credit', 'amount' => $liability, 'description' => 'Lease liability at PV of payments'],
        ];

        if ($idc > 0) {
            $bankId = $response['bank_account_id'] ?? null;
            if ($bankId) {
                $lines[] = ['chart_of_account_id' => $bankId, 'type' => 'credit', 'amount' => $idc, 'description' => 'Initial direct costs paid'];
            }
        }

        $this->record($company, $user, $lease, $lease->commencement_date->format('Y-m-d'), 'Lease commencement — '.$lease->name, $lines, $response);
    }

    private function postLessorFinanceCommencement(Lease $lease, User $user, Company $company, $accounts, array $response): void
    {
        $netInvId = $this->resolveOrCreate($company, $response['net_investment_account_id'], 'Net Investment in Lease', '1004050', 'assets');
        $bankId = $response['bank_account_id'] ?? null;

        $netInvestment = (float) $lease->net_investment;

        $lines = [
            ['chart_of_account_id' => $netInvId, 'type' => 'debit', 'amount' => $netInvestment, 'description' => 'Net investment in lease recognised'],
        ];

        if ($bankId) {
            $lines[] = ['chart_of_account_id' => $bankId, 'type' => 'credit', 'amount' => $netInvestment, 'description' => 'Underlying asset derecognised'];
        }

        $this->record($company, $user, $lease, $lease->commencement_date->format('Y-m-d'), 'Finance lease commencement — '.$lease->name, $lines, $response);
    }

    public function postMonthlyRouDepreciationWithAi(Lease $lease, User $user, \Carbon\CarbonImmutable $monthEnd): ?Lease
    {
        if (! $lease->isLessee() || $lease->status === 'terminated') {
            return null;
        }

        if ($lease->last_depreciation_posted_on
            && \Carbon\CarbonImmutable::parse($lease->last_depreciation_posted_on)->greaterThanOrEqualTo($monthEnd)
        ) {
            return null;
        }

        $rouCost = (float) $lease->rou_asset_cost;
        $termMonths = (int) $lease->lease_term_months;
        if ($rouCost <= 0 || $termMonths <= 0) {
            return null;
        }

        $monthly = round($rouCost / $termMonths, 2);

        $company = $lease->company;
        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($lease, $accounts, 'rou_depreciation', [
            'month_end' => $monthEnd->format('Y-m-d'),
            'monthly_amount' => $monthly,
        ]);

        $depExpId = $this->resolveOrCreate($company, $response['depreciation_expense_account_id'] ?? null,
            'Depreciation — Right-of-Use Assets', '6007060', 'expenses');
        $accDepId = $this->resolveOrCreate($company, $response['accumulated_depreciation_account_id'] ?? null,
            'Accumulated Depreciation — ROU Assets', '1009050', 'assets', true);

        $lines = [
            ['chart_of_account_id' => $depExpId, 'type' => 'debit',  'amount' => $monthly, 'description' => 'ROU depreciation '.$monthEnd->format('Y-m').': '.$lease->name],
            ['chart_of_account_id' => $accDepId, 'type' => 'credit', 'amount' => $monthly, 'description' => 'ROU depreciation '.$monthEnd->format('Y-m').': '.$lease->name],
        ];

        return DB::transaction(function () use ($lease, $user, $company, $lines, $monthEnd, $monthly, $response) {
            $txn = $this->record($company, $user, $lease, $monthEnd->format('Y-m-d'),
                'ROU depreciation — '.$lease->name.' ('.$monthEnd->format('M Y').')', $lines, $response);

            LeaseEvent::create([
                'lease_id'       => $lease->id,
                'event_date'     => $monthEnd->format('Y-m-d'),
                'type'           => 'rou_depreciation',
                'amount'         => $monthly,
                'description'    => 'ROU depreciation '.$monthEnd->format('M Y').': R '.number_format($monthly, 2),
                'journal_status' => LeaseEvent::STATUS_POSTED,
                'transaction_id' => $txn->id,
            ]);

            $lease->forceFill(['last_depreciation_posted_on' => $monthEnd->format('Y-m-d')])->save();
            return $lease;
        });
    }

    private function record(Company $company, User $user, Lease $lease, string $date, string $description, array $lines, array $response): \App\Models\Transaction
    {
        return DB::transaction(function () use ($company, $user, $lease, $date, $description, $lines, $response) {
            return $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description' => $description,
                'reference' => $lease->asset_tag ?: 'LEASE-'.$lease->id,
                'status' => 'draft',
                'source_document' => 'lease:'.$lease->id,
                'notes' => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines' => $lines,
            ]);
        });
    }

    private function resolveOrCreate(Company $company, ?int $aiPick, string $name, string $codePrefix, string $type, bool $isContra = false): int
    {
        if ($aiPick && ChartOfAccount::where('company_id', $company->id)->where('id', $aiPick)->exists()) {
            return $aiPick;
        }

        $existing = ChartOfAccount::where('company_id', $company->id)
            ->where('account_name', 'like', $name.'%')
            ->where('account_type', $type)
            ->first();
        if ($existing) return $existing->id;

        $maxCode = ChartOfAccount::where('company_id', $company->id)
            ->where('account_code', 'like', $codePrefix.'%')
            ->max('account_code');
        $code = $maxCode ? (string) ((int) $maxCode + 1) : $codePrefix.'0';

        return ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => $type,
            'is_active' => true,
            'is_contra' => $isContra,
            'is_lease_asset' => in_array($type, ['assets', 'liabilities']),
        ])->id;
    }

    private function companyAccounts(Company $company)
    {
        if (isset($this->accountCache[$company->id])) {
            return $this->accountCache[$company->id];
        }

        return $this->accountCache[$company->id] = $this->accountSearch->searchMultiple($company->id, [
            'right of use asset lease IFRS 16',
            'lease liability long term',
            'interest expense finance cost',
            'depreciation expense right of use',
            'lease income revenue operating lessor',
            'lease receivable finance lessor',
            'bank cash proceeds payment',
            'accounts payable creditor liability',
        ], 10);
    }

    private function prompt(Lease $lease, $accounts, string $kind, array $extra = []): array
    {
        $list = $accounts
            ->map(fn (ChartOfAccount $a) => "- id={$a->id}, code={$a->account_code}, name=\"{$a->account_name}\", type={$a->account_type}".($a->is_contra ? ' (contra)' : ''))
            ->implode("\n");

        $role = $lease->role ?? 'lessee';
        $classification = $lease->classification ?? 'n/a';

        $details = "Lease: {$lease->name} (tag {$lease->asset_tag})\n"
            ."Role: {$role}, Classification: {$classification}\n"
            ."Category: {$lease->category}, Counterparty: {$lease->counterparty}\n"
            ."Monthly payment: {$lease->monthly_payment}, IBR: {$lease->incremental_borrowing_rate}\n"
            ."Term: {$lease->lease_term_months} months, Commencement: {$lease->commencement_date->format('Y-m-d')}\n";

        if ($lease->isLessee()) {
            $details .= "ROU asset cost: {$lease->rou_asset_cost}, Lease liability opening: {$lease->lease_liability_opening}\n"
                ."Accumulated impairment: ".($lease->accumulated_impairment ?? 0)."\n";
        } elseif ($lease->isFinanceLease()) {
            $details .= "Net investment: {$lease->net_investment}, Unearned finance income: {$lease->unearned_finance_income}\n"
                ."Asset fair value: ".($lease->asset_fair_value ?? 0).", Unguaranteed residual: ".($lease->unguaranteed_residual ?? 0)."\n";
        }

        $extraStr = empty($extra) ? '' : "\nAdditional context:\n".json_encode($extra, JSON_PRETTY_PRINT)."\n";

        $prompt = "Posting type: {$kind}\n\n{$details}{$extraStr}\nChart of accounts:\n{$list}\n\nPick the IFRS 16-appropriate accounts for this lease posting.";

        return (new LeasePostingAgent)->prompt($prompt)->toArray();
    }
}
