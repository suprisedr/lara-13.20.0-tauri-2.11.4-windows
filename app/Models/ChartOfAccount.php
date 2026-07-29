<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BroadcastsChanges;

class ChartOfAccount extends Model
{
    /** @use HasFactory<\Database\Factories\ChartOfAccountFactory> */
    use HasFactory;
    use BroadcastsChanges;

    protected $fillable = [
        'company_id',
        'account_code',
        'account_name',
        'account_type',
        'category',
        'cash_flow_category',
        'is_contra',
        'is_ppe',
        'is_intangible',
        'is_inventory',
        'is_investment_property',
        'is_biological_asset',
        'is_lease_asset',
        'is_cash',
        'is_oci',
        'description',
        'parent_code',
        'parent_id',
        'show_separately',
        'is_active',
        'opening_balance',
        'is_embedded',
        'embedded_at',
    ];

    /** Valid cash-flow statement categories an account can be tied to. */
    public const CASH_FLOW_CATEGORIES = ['operating', 'investing', 'financing'];

    protected function casts(): array
    {
        return [
            'is_active'       => 'boolean',
            'is_contra'       => 'boolean',
            'is_ppe'          => 'boolean',
            'is_intangible'   => 'boolean',
            'is_inventory'    => 'boolean',
            'is_investment_property' => 'boolean',
            'is_biological_asset'    => 'boolean',
            'is_lease_asset'         => 'boolean',
            'is_cash'                => 'boolean',
            'is_oci'          => 'boolean',
            'show_separately' => 'boolean',
            'opening_balance' => 'decimal:2',
            'is_embedded'     => 'boolean',
            'embedded_at'     => 'datetime',
        ];
    }

    public function toEmbeddableText(): string
    {
        $parts = [
            "code:{$this->account_code}",
            "name:{$this->account_name}",
            "type:{$this->account_type}",
        ];

        if ($this->category) {
            $parts[] = "category:{$this->category}";
        }

        if ($this->description) {
            $parts[] = "description:{$this->description}";
        }

        if ($this->is_contra) {
            $parts[] = 'contra:true';
        }

        return implode(' ', $parts);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'chart_of_account_id');
    }

    /** Sub-accounts (items) belonging to this parent account. */
    public function items(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    /** The parent (group) account this account belongs to. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    /** Accounts that can receive postings — parent/group accounts (which have sub-accounts) are excluded. */
    public function scopePostable($query)
    {
        return $query->whereDoesntHave('items');
    }
}
