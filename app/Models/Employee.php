<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BroadcastsChanges;

class Employee extends Model
{
    use BroadcastsChanges;
    protected $fillable = [
        'company_id',
        'employee_number',
        'first_name',
        'last_name',
        'id_number',
        'passport_number',
        'tax_reference_number',
        'date_of_birth',
        'gender',
        'employment_type',
        'job_title',
        'department',
        'start_date',
        'end_date',
        'pay_frequency',
        'pay_day_of_month',
        'pay_cycle_anchor',
        'pay_type',
        'basic_salary',
        'hourly_rate',
        'medical_aid_members',
        'medical_aid_employee_contribution',
        'retirement_fund_contribution',
        'retirement_fund_type',
        'employer_retirement_contribution',
        'medical_aid_employer_contribution',
        'leave_days_per_year',
        'leave_balance_days',
        'bonus_months',
        'bank_name',
        'bank_account_number',
        'bank_account_type',
        'bank_branch_code',
        'address_line_1',
        'address_line_2',
        'city',
        'province',
        'postal_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'                    => 'date',
            'start_date'                       => 'date',
            'end_date'                         => 'date',
            'pay_day_of_month'                 => 'integer',
            'pay_cycle_anchor'                 => 'date',
            'basic_salary'                     => 'decimal:2',
            'hourly_rate'                      => 'decimal:2',
            'medical_aid_employee_contribution'  => 'decimal:2',
            'retirement_fund_contribution'      => 'decimal:2',
            'employer_retirement_contribution'  => 'decimal:2',
            'medical_aid_employer_contribution' => 'decimal:2',
            'leave_days_per_year'               => 'decimal:1',
            'leave_balance_days'                => 'decimal:2',
            'bonus_months'                      => 'decimal:2',
            'medical_aid_members'               => 'integer',
            'is_active'                         => 'boolean',
        ];
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payrollComponents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(PayrollComponent::class, 'employee_payroll_components')
            ->withPivot(['amount', 'is_active'])
            ->withTimestamps();
    }

    public function activePayrollComponents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->payrollComponents()->wherePivot('is_active', true)->where('payroll_components.is_active', true);
    }

    public function payslips(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public static function generateEmployeeNumber(int $companyId): string
    {
        $last = static::where('company_id', $companyId)
            ->orderByDesc('id')
            ->value('employee_number');

        if (! $last) {
            return 'EMP001';
        }

        $num = (int) filter_var($last, FILTER_SANITIZE_NUMBER_INT);

        return 'EMP' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }

    public static function employmentTypes(): array
    {
        return [
            'permanent'  => 'Permanent',
            'contract'   => 'Fixed-Term Contract',
            'part_time'  => 'Part-Time',
            'casual'     => 'Casual',
        ];
    }

    public static function payFrequencies(): array
    {
        return [
            'monthly'     => 'Monthly',
            'fortnightly' => 'Fortnightly',
            'weekly'      => 'Weekly',
        ];
    }

    public static function payTypes(): array
    {
        return [
            'salary'  => 'Fixed Salary',
            'hourly'  => 'Hourly Rate',
        ];
    }

    public function isHourly(): bool
    {
        return $this->pay_type === 'hourly';
    }
}
