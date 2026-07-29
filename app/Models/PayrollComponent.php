<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BroadcastsChanges;

class PayrollComponent extends Model
{
    use BroadcastsChanges;
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'category',
        'is_taxable',
        'is_pensionable',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_taxable'    => 'boolean',
            'is_pensionable' => 'boolean',
            'is_active'     => 'boolean',
            'sort_order'    => 'integer',
        ];
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_payroll_components')
            ->withPivot(['amount', 'is_active'])
            ->withTimestamps();
    }

    public static function types(): array
    {
        return [
            'earning'               => 'Earning',
            'deduction'             => 'Deduction',
            'employer_contribution' => 'Employer Contribution',
        ];
    }

    public static function categories(): array
    {
        return [
            'basic'               => 'Basic Salary',
            'overtime'            => 'Overtime',
            'commission'          => 'Commission',
            'bonus'               => 'Bonus / 13th Cheque',
            'travel_allowance'    => 'Travel Allowance',
            'housing_allowance'   => 'Housing Allowance',
            'car_allowance'       => 'Car Allowance',
            'meal_allowance'      => 'Meal Allowance',
            'medical_aid'         => 'Medical Aid',
            'pension_fund'        => 'Pension Fund',
            'retirement_annuity'  => 'Retirement Annuity',
            'loan_repayment'      => 'Loan Repayment',
            'other'               => 'Other',
        ];
    }
}
