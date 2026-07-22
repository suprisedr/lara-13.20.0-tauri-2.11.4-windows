<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    protected $fillable = [
        'company_id',
        'period_start',
        'period_end',
        'status',
        'notes',
        'total_gross_earnings',
        'total_paye',
        'total_uif_employee',
        'total_uif_employer',
        'total_sdl',
        'total_other_deductions',
        'total_net_pay',
        'employee_type',
        'pay_frequency',
        'total_employer_cost',
        'total_employer_retirement',
        'total_employer_medical_aid',
        'total_leave_accrual',
        'total_bonus_accrual',
        'wages_expense_account_id',
        'paye_payable_account_id',
        'uif_payable_account_id',
        'sdl_payable_account_id',
        'net_wages_payable_account_id',
        'retirement_payable_account_id',
        'medical_aid_payable_account_id',
        'leave_accrual_account_id',
        'bonus_provision_account_id',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'period_start'           => 'date',
            'period_end'             => 'date',
            'total_gross_earnings'   => 'decimal:2',
            'total_paye'             => 'decimal:2',
            'total_uif_employee'     => 'decimal:2',
            'total_uif_employer'     => 'decimal:2',
            'total_sdl'              => 'decimal:2',
            'total_other_deductions' => 'decimal:2',
            'total_net_pay'               => 'decimal:2',
            'total_employer_cost'         => 'decimal:2',
            'total_employer_retirement'   => 'decimal:2',
            'total_employer_medical_aid'  => 'decimal:2',
            'total_leave_accrual'         => 'decimal:2',
            'total_bonus_accrual'         => 'decimal:2',
        ];
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payslips(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function transaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function wagesExpenseAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'wages_expense_account_id');
    }

    public function payePayableAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'paye_payable_account_id');
    }

    public function uifPayableAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'uif_payable_account_id');
    }

    public function sdlPayableAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'sdl_payable_account_id');
    }

    public function netWagesPayableAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'net_wages_payable_account_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function getPeriodLabelAttribute(): string
    {
        return $this->period_start->format('d M Y') . ' – ' . $this->period_end->format('d M Y');
    }
}
