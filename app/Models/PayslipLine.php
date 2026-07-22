<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayslipLine extends Model
{
    protected $fillable = [
        'payslip_id',
        'payroll_component_id',
        'type',
        'description',
        'amount',
        'is_statutory',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'is_statutory' => 'boolean',
            'sort_order'   => 'integer',
        ];
    }

    public function payslip(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    public function payrollComponent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
    }
}
