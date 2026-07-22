<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lease extends Model
{
    public const ROLES = [
        'lessee' => 'Lessee',
        'lessor' => 'Lessor',
    ];

    public const CLASSIFICATIONS = [
        'finance'   => 'Finance Lease',
        'operating' => 'Operating Lease',
    ];

    public const CATEGORIES = [
        'property'  => 'Property / Buildings',
        'vehicle'   => 'Motor Vehicles',
        'equipment' => 'Equipment & Machinery',
        'it'        => 'IT Equipment',
        'other'     => 'Other',
    ];

    public const PAYMENT_FREQUENCIES = [
        'monthly'   => 'Monthly',
        'quarterly' => 'Quarterly',
        'annually'  => 'Annually',
    ];

    protected $fillable = [
        'company_id',
        'role',
        'classification',
        'name',
        'asset_tag',
        'category',
        'counterparty',
        'commencement_date',
        'end_date',
        'lease_term_months',
        'monthly_payment',
        'payment_frequency',
        'incremental_borrowing_rate',
        'initial_direct_costs',
        'lease_liability_opening',
        'rou_asset_cost',
        'residual_value_guarantee',
        'is_short_term',
        'is_low_value',
        'status',
        'termination_date',
        'termination_gain_loss',
        'accumulated_impairment',
        'net_investment',
        'unearned_finance_income',
        'asset_fair_value',
        'unguaranteed_residual',
        'notes',
        'location',
        'last_depreciation_posted_on',
    ];

    protected function casts(): array
    {
        return [
            'commencement_date' => 'date',
            'end_date' => 'date',
            'termination_date' => 'date',
            'monthly_payment' => 'decimal:2',
            'incremental_borrowing_rate' => 'decimal:4',
            'initial_direct_costs' => 'decimal:2',
            'lease_liability_opening' => 'decimal:2',
            'rou_asset_cost' => 'decimal:2',
            'residual_value_guarantee' => 'decimal:2',
            'termination_gain_loss' => 'decimal:2',
            'accumulated_impairment' => 'decimal:2',
            'net_investment' => 'decimal:2',
            'unearned_finance_income' => 'decimal:2',
            'asset_fair_value' => 'decimal:2',
            'unguaranteed_residual' => 'decimal:2',
            'last_depreciation_posted_on' => 'date',
            'is_short_term' => 'boolean',
            'is_low_value' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(LeaseEvent::class)->orderByDesc('event_date')->orderByDesc('id');
    }

    public function isTerminated(): bool
    {
        return $this->termination_date !== null;
    }

    public function isExpired(string $asOfDate = null): bool
    {
        $asOf = Carbon::parse($asOfDate ?? now()->toDateString());
        return $this->end_date->lessThan($asOf) || $this->isTerminated();
    }

    public function isExempt(): bool
    {
        return $this->is_short_term || $this->is_low_value;
    }

    public function remainingMonths(string $asOfDate = null): int
    {
        $asOf = Carbon::parse($asOfDate ?? now()->toDateString());
        if ($this->isTerminated()) {
            return 0;
        }
        $remaining = $asOf->diffInMonths($this->end_date, false);
        return max(0, (int) $remaining);
    }

    public function accumulatedDepreciation(string $asOfDate): float
    {
        $cost = (float) $this->rou_asset_cost;
        if ($cost <= 0 || $this->lease_term_months <= 0) return 0.0;

        $asOf = Carbon::parse($asOfDate);
        if ($this->isTerminated() && $this->termination_date->lessThan($asOf)) {
            $asOf = $this->termination_date;
        }
        if ($asOf->lessThan($this->commencement_date)) return 0.0;

        $monthsInUse = $this->commencement_date->diffInMonths($asOf);
        $totalMonths = (int) $this->lease_term_months;
        $depreciable = $cost - (float) ($this->residual_value_guarantee ?? 0);
        if ($depreciable <= 0) return 0.0;

        $accDep = $depreciable * min($monthsInUse, $totalMonths) / $totalMonths;
        return round(min($accDep, $depreciable), 2);
    }

    public function rouNetBookValue(string $asOfDate): float
    {
        $cost = (float) $this->rou_asset_cost;
        $accDep = $this->accumulatedDepreciation($asOfDate);
        $accImp = (float) ($this->accumulated_impairment ?? 0);
        return round(max(0, $cost - $accDep - $accImp), 2);
    }

    public function leaseLiabilityBalance(string $asOfDate): float
    {
        $asOf = Carbon::parse($asOfDate);
        if ($asOf->lessThan($this->commencement_date)) return (float) $this->lease_liability_opening;
        if ($this->isTerminated() && $this->termination_date->lessThan($asOf)) return 0.0;

        $opening = (float) $this->lease_liability_opening;
        $monthlyRate = (float) $this->incremental_borrowing_rate / 12;
        $payment = (float) $this->monthly_payment;
        $months = $this->commencement_date->diffInMonths($asOf);
        $totalMonths = (int) $this->lease_term_months;

        $balance = $opening;
        for ($i = 0; $i < min($months, $totalMonths); $i++) {
            $interest = $balance * $monthlyRate;
            $balance = $balance + $interest - $payment;
            if ($balance < 0) { $balance = 0; break; }
        }

        return round(max(0, $balance), 2);
    }

    public function totalInterestExpense(string $asOfDate): float
    {
        $asOf = Carbon::parse($asOfDate);
        if ($asOf->lessThan($this->commencement_date)) return 0.0;

        $opening = (float) $this->lease_liability_opening;
        $monthlyRate = (float) $this->incremental_borrowing_rate / 12;
        $payment = (float) $this->monthly_payment;
        $months = $this->commencement_date->diffInMonths($asOf);
        $totalMonths = (int) $this->lease_term_months;

        $totalInterest = 0.0;
        $balance = $opening;
        for ($i = 0; $i < min($months, $totalMonths); $i++) {
            $interest = $balance * $monthlyRate;
            $totalInterest += $interest;
            $balance = $balance + $interest - $payment;
            if ($balance <= 0) break;
        }

        return round($totalInterest, 2);
    }

    public static function presentValueOfPayments(float $payment, int $months, float $annualRate): float
    {
        if ($annualRate <= 0 || $months <= 0) return $payment * $months;
        $r = $annualRate / 12;
        return round($payment * (1 - pow(1 + $r, -$months)) / $r, 2);
    }

    // ─── Role helpers ──────────────────────────────────────────────────

    public function isLessee(): bool
    {
        return ($this->role ?? 'lessee') === 'lessee';
    }

    public function isLessor(): bool
    {
        return $this->role === 'lessor';
    }

    public function isFinanceLease(): bool
    {
        return $this->classification === 'finance';
    }

    public function isOperatingLease(): bool
    {
        return $this->classification === 'operating';
    }

    // ─── Lessor: operating lease ────────────────────────────────────────

    public function operatingLeaseIncomeToDate(string $asOfDate): float
    {
        if (!$this->isOperatingLease()) return 0.0;

        $asOf = Carbon::parse($asOfDate);
        if ($asOf->lessThan($this->commencement_date)) return 0.0;

        $monthlyIncome = (float) $this->monthly_payment;
        $months = $this->commencement_date->diffInMonths($asOf);
        $totalMonths = (int) $this->lease_term_months;

        return round($monthlyIncome * min($months, $totalMonths), 2);
    }

    // ─── Lessor: finance lease ──────────────────────────────────────────

    public function netInvestmentBalance(string $asOfDate): float
    {
        if (!$this->isFinanceLease()) return 0.0;

        $asOf = Carbon::parse($asOfDate);
        if ($asOf->lessThan($this->commencement_date)) return (float) $this->net_investment;
        if ($this->isTerminated() && $this->termination_date->lessThan($asOf)) return 0.0;

        $balance = (float) $this->net_investment;
        $monthlyRate = (float) $this->incremental_borrowing_rate / 12;
        $payment = (float) $this->monthly_payment;
        $months = $this->commencement_date->diffInMonths($asOf);
        $totalMonths = (int) $this->lease_term_months;

        for ($i = 0; $i < min($months, $totalMonths); $i++) {
            $interest = $balance * $monthlyRate;
            $balance = $balance + $interest - $payment;
            if ($balance < 0) { $balance = 0; break; }
        }

        return round(max(0, $balance), 2);
    }

    public function financeIncomeToDate(string $asOfDate): float
    {
        if (!$this->isFinanceLease()) return 0.0;

        $asOf = Carbon::parse($asOfDate);
        if ($asOf->lessThan($this->commencement_date)) return 0.0;

        $balance = (float) $this->net_investment;
        $monthlyRate = (float) $this->incremental_borrowing_rate / 12;
        $payment = (float) $this->monthly_payment;
        $months = $this->commencement_date->diffInMonths($asOf);
        $totalMonths = (int) $this->lease_term_months;
        $totalIncome = 0.0;

        for ($i = 0; $i < min($months, $totalMonths); $i++) {
            $interest = $balance * $monthlyRate;
            $totalIncome += $interest;
            $balance = $balance + $interest - $payment;
            if ($balance <= 0) break;
        }

        return round($totalIncome, 2);
    }

    public function unearnedIncomeBalance(string $asOfDate): float
    {
        if (!$this->isFinanceLease()) return 0.0;
        $earned = $this->financeIncomeToDate($asOfDate);
        return round(max(0, (float) $this->unearned_finance_income - $earned), 2);
    }
}
