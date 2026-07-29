<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BroadcastsChanges;

class DeliveryNote extends Model
{
    use BroadcastsChanges;
    protected $fillable = [
        'company_id',
        'invoice_id',
        'customer_id',
        'delivery_note_number',
        'customer_name',
        'customer_email',
        'delivery_address',
        'delivery_date',
        'expected_delivery_date',
        'status',
        'dispatched_by',
        'vehicle_registration',
        'tracking_reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date'          => 'date',
            'expected_delivery_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft'      => 'Draft',
            'dispatched' => 'Dispatched',
            'delivered'  => 'Delivered',
            'cancelled'  => 'Cancelled',
            default      => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'draft'      => '#6b7280',
            'dispatched' => '#d97706',
            'delivered'  => '#16a34a',
            'cancelled'  => '#dc2626',
            default      => '#000',
        };
    }
}
