<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BroadcastsChanges;

class RelatedParty extends Model
{
    use BroadcastsChanges;

    public const TYPE_PARENT              = 'parent';
    public const TYPE_SUBSIDIARY          = 'subsidiary';
    public const TYPE_ASSOCIATE           = 'associate';
    public const TYPE_JOINT_VENTURE       = 'joint_venture';
    public const TYPE_KEY_MANAGEMENT      = 'key_management';
    public const TYPE_CLOSE_FAMILY        = 'close_family';
    public const TYPE_ENTITY_COMMON_KMP   = 'entity_with_common_kmp';
    public const TYPE_POST_EMPLOYMENT     = 'post_employment_plan';
    public const TYPE_OTHER               = 'other';

    public const TYPES = [
        self::TYPE_PARENT            => 'Parent',
        self::TYPE_SUBSIDIARY        => 'Subsidiary',
        self::TYPE_ASSOCIATE         => 'Associate',
        self::TYPE_JOINT_VENTURE     => 'Joint Venture',
        self::TYPE_KEY_MANAGEMENT    => 'Key Management Personnel',
        self::TYPE_CLOSE_FAMILY      => 'Close Family Member',
        self::TYPE_ENTITY_COMMON_KMP => 'Entity with Common KMP',
        self::TYPE_POST_EMPLOYMENT   => 'Post-employment Benefit Plan',
        self::TYPE_OTHER             => 'Other',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'relationship_type',
        'description',
        'contact_person',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function relatedPartyTransactions(): HasMany
    {
        return $this->hasMany(RelatedPartyTransaction::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
