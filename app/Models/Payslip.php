<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'pay_date',
        'hours_worked',
        'override_gross_earnings',
        'is_adjusted',
        'gross_earnings',
        'paye',
        'uif_employee',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'uif_employer',
        'sdl',
        'total_employer_cost',
        'employer_retirement',
        'employer_medical_aid',
        'leave_accrual',
        'bonus_accrual',
        'annual_equivalent_income',
        'taxable_income',
        'annual_tax_before_rebates',
        'annual_tax_after_rebates',
        'medical_aid_credit_monthly',
    ];

    protected function casts(): array
    {
        return [
            'pay_date'                  => 'date',
            'hours_worked'              => 'decimal:2',
            'override_gross_earnings'   => 'decimal:2',
            'is_adjusted'               => 'boolean',
            'gross_earnings'            => 'decimal:2',
            'paye'                      => 'decimal:2',
            'uif_employee'              => 'decimal:2',
            'other_deductions'          => 'decimal:2',
            'total_deductions'          => 'decimal:2',
            'net_pay'                   => 'decimal:2',
            'uif_employer'              => 'decimal:2',
            'sdl'                       => 'decimal:2',
            'total_employer_cost'       => 'decimal:2',
            'employer_retirement'       => 'decimal:2',
            'employer_medical_aid'      => 'decimal:2',
            'leave_accrual'             => 'decimal:2',
            'bonus_accrual'             => 'decimal:2',
            'annual_equivalent_income'  => 'decimal:2',
            'taxable_income'            => 'decimal:2',
            'annual_tax_before_rebates' => 'decimal:2',
            'annual_tax_after_rebates'  => 'decimal:2',
            'medical_aid_credit_monthly' => 'decimal:2',
        ];
    }

    public function payrollRun(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function lines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PayslipLine::class)->orderBy('sort_order')->orderBy('type');
    }

    public function earningLines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PayslipLine::class)->where('type', 'earning')->orderBy('sort_order');
    }

    public function deductionLines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PayslipLine::class)->whereIn('type', ['deduction', 'statutory_deduction'])->orderBy('sort_order');
    }

    public function employerLines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PayslipLine::class)->where('type', 'employer_contribution')->orderBy('sort_order');
    }
}
