<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BroadcastsChanges;

class RevenueContract extends Model
{
    use BroadcastsChanges;

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_MODIFIED  = 'modified';

    protected $fillable = [
        'company_id',
        'customer_id',
        'contract_reference',
        'name',
        'status',
        'inception_date',
        'completion_date',
        'total_transaction_price',
        'variable_consideration_estimate',
        'variable_consideration_constraint',
        'significant_financing_component',
        'contract_modification_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'inception_date'                    => 'date',
            'completion_date'                   => 'date',
            'total_transaction_price'           => 'decimal:2',
            'variable_consideration_estimate'   => 'decimal:2',
            'variable_consideration_constraint' => 'decimal:2',
            'significant_financing_component'   => 'decimal:2',
            'contract_modification_date'        => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function performanceObligations(): HasMany
    {
        return $this->hasMany(PerformanceObligation::class)->orderBy('sort_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RevenueContractEvent::class);
    }

    public function totalRevenueRecognised(): float
    {
        return (float) $this->performanceObligations()->sum('revenue_recognised');
    }

    public function remainingRevenue(): float
    {
        return max((float) $this->total_transaction_price - $this->totalRevenueRecognised(), 0);
    }

    public function contractAssetBalance(): float
    {
        $recognised = $this->totalRevenueRecognised();
        $billed     = 0; // simplified — would compare against invoices
        return max($recognised - $billed, 0);
    }

    public function contractLiabilityBalance(): float
    {
        $recognised = $this->totalRevenueRecognised();
        $billed     = 0;
        return max($billed - $recognised, 0);
    }

    public function allocateTransactionPrice(): void
    {
        $obligations = $this->performanceObligations;
        $totalSSP    = $obligations->sum('standalone_selling_price');

        if ($totalSSP <= 0) return;

        foreach ($obligations as $ob) {
            $ratio     = (float) $ob->standalone_selling_price / $totalSSP;
            $allocated = round((float) $this->total_transaction_price * $ratio, 2);
            $ob->update(['allocated_transaction_price' => $allocated]);
        }
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
