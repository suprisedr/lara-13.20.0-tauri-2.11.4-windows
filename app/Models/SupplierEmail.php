<?php

namespace App\Models;

use App\Enums\SupplierEmailStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_email_account_id',
        'supplier_id',
        'message_id',
        'from_email',
        'from_name',
        'subject',
        'body_text',
        'ai_summary',
        'received_at',
        'is_reviewed',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'is_reviewed' => 'boolean',
            'status' => SupplierEmailStatus::class,
        ];
    }

    public function emailAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CompanyEmailAccount::class, 'company_email_account_id');
    }

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupplierEmailAttachment::class);
    }

    public function supplierInvoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function purchaseOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
