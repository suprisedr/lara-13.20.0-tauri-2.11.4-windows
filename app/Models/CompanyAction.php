<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyAction extends Model
{
    public const PRIORITY_LOW    = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH   = 'high';

    public const SOURCE_MANUAL   = 'manual';
    public const SOURCE_AI_AGENT = 'ai-agent';
    public const SOURCE_SYSTEM   = 'system';

    protected $fillable = [
        'company_id',
        'title',
        'body',
        'priority',
        'source',
        'related_type',
        'related_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    /** Priority badge colour for the UI. */
    public function priorityColour(): string
    {
        return match ($this->priority) {
            self::PRIORITY_HIGH => '#dc2626',
            self::PRIORITY_LOW  => '#6b7280',
            default             => '#d97706',
        };
    }
}
