<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialStatementNote extends Model
{
    public const KIND_TEXT        = 'text';
    public const KIND_PPE         = 'ppe';
    public const KIND_INTANGIBLE  = 'intangible';
    public const KIND_INVENTORY   = 'inventory';

    protected $fillable = [
        'company_id',
        'slug',
        'note_number',
        'title',
        'body',
        'kind',
        'sort_order',
        'is_active',
        'include_in_afs',
    ];

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'include_in_afs' => 'boolean',
            'note_number' => 'integer',
            'sort_order'  => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function accountLinks(): HasMany
    {
        return $this->hasMany(NoteAccountLink::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Alias used for route-model scoped binding of {note}/lines/{line}. */
    public function lines(): HasMany
    {
        return $this->hasMany(NoteAccountLink::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPpe(): bool
    {
        return $this->kind === self::KIND_PPE;
    }

    public function isIntangible(): bool
    {
        return $this->kind === self::KIND_INTANGIBLE;
    }

    public function isInventory(): bool
    {
        return $this->kind === self::KIND_INVENTORY;
    }
}
