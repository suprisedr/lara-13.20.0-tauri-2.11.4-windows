<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One narrative heading of a company's business plan.
 *
 * The sibling of {@see FinancialStatementNote}: same shape, same editing
 * story, rendered by the same Word pipeline.
 */
class BusinessPlanSection extends Model
{
    protected $fillable = [
        'company_id',
        'slug',
        'section_number',
        'title',
        'body',
        'sort_order',
        'is_active',
        'include_statistics',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'include_statistics' => 'boolean',
            'section_number' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
