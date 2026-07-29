<?php

namespace App\Models;

use App\Enums\StockMovementAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BroadcastsChanges;

class InventoryMovement extends Model
{
    use HasFactory;
    use BroadcastsChanges;

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED  = 'posted';
    public const STATUS_FAILED  = 'failed';

    protected $fillable = [
        'company_id',
        'inventory_item_id',
        'invoice_id',
        'action',
        'quantity',
        'unit_cost',
        'reference',
        'notes',
        'moved_at',
        'created_by',
        'journal_status',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'action' => StockMovementAction::class,
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:2',
            'moved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
