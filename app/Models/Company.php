<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CompanyAction;

class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parent_company_id',
        'is_consolidation_parent',
        'group_ownership_percentage',
        'acquisition_date',
        'investment_cost',
        'equity_at_acquisition',
        'control_acquired_date',
        'control_lost_date',
        'registered_name',
        'slug',
        'company_type',
        'registration_number',
        'financial_year_end_month',
        'address_line_1',
        'address_line_2',
        'city',
        'province',
        'postal_code',
        'income_tax_number',
        'vat_number',
        'paye_number',
        'uif_number',
        'sdl_number',
        'corporate_tax_rate',
        'chart_of_accounts_type',
        'industry',
        'bank_name',
        'bank_account_number',
        'bank_account_type',
        'bank_branch_code',
        'logo_path',
        'onboarding_step',
        'onboarding_completed_at',
        'status',
        'relationship_type',
        'is_joint_venture',
    ];

    public const RELATIONSHIP_TYPES = [
        'investment'  => ['label' => 'Financial Investment', 'standard' => 'IFRS 9',  'method' => 'Fair Value'],
        'associate'   => ['label' => 'Associate',           'standard' => 'IAS 28',  'method' => 'Equity Method'],
        'joint_venture'=> ['label' => 'Joint Venture',      'standard' => 'IAS 28',  'method' => 'Equity Method'],
        'subsidiary'  => ['label' => 'Subsidiary',          'standard' => 'IFRS 10', 'method' => 'Full Consolidation'],
    ];

    protected function casts(): array
    {
        return [
            'onboarding_completed_at' => 'datetime',
            'financial_year_end_month' => 'integer',
            'onboarding_step' => 'integer',
            'is_consolidation_parent' => 'boolean',
            'is_joint_venture' => 'boolean',
            'group_ownership_percentage' => 'decimal:2',
            'acquisition_date' => 'date',
            'investment_cost' => 'decimal:2',
            'equity_at_acquisition' => 'decimal:2',
            'control_acquired_date' => 'date',
            'control_lost_date' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (self $company) {
            if (empty($company->slug)) {
                $company->slug = self::generateUniqueSlug($company->registered_name);
            }
        });

        static::updating(function (self $company) {
            if ($company->isDirty('registered_name') && ! $company->isDirty('slug')) {
                $company->slug = self::generateUniqueSlug($company->registered_name, $company->id);
            }
        });
    }

    private static function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = \Illuminate\Support\Str::slug($name);
        $slug = $base;
        $i    = 2;

        while (
            static::where('slug', $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The group parent this company is consolidated into (if it is a subsidiary). */
    public function parentCompany(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    /** Direct subsidiaries consolidated into this company. */
    public function subsidiaries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    /** Ownership-change events recorded by this company as group parent. */
    public function ownershipEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubsidiaryOwnershipEvent::class, 'parent_company_id')
            ->orderBy('event_date');
    }

    /** Ownership-change events affecting this company as a subsidiary. */
    public function ownershipEventsAsSubsidiary(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubsidiaryOwnershipEvent::class, 'subsidiary_company_id')
            ->orderBy('event_date');
    }

    /**
     * Whether the parent controls this subsidiary as at a date. Legacy
     * subsidiaries without explicit control dates are treated as controlled
     * throughout (snapshot behaviour).
     */
    public function isControlledAt(string $date): bool
    {
        if ($this->control_acquired_date && $this->control_acquired_date->gt(\Illuminate\Support\Carbon::parse($date))) {
            return false;
        }
        if ($this->control_lost_date && $this->control_lost_date->lte(\Illuminate\Support\Carbon::parse($date))) {
            return false;
        }

        return true;
    }

    /** Subsidiaries still controlled today (not yet deconsolidated). */
    public function activeSubsidiaries(): \Illuminate\Support\Collection
    {
        $today = now()->toDateString();

        return $this->subsidiaries()->orderBy('registered_name')->get()
            ->filter(fn(Company $s) => $s->isControlledAt($today))
            ->values();
    }

    /** The parent's holding fraction in this subsidiary as at a date (from events, else snapshot). */
    public function ownershipFractionAt(string $date): float
    {
        $event = $this->ownershipEventsAsSubsidiary()
            ->whereDate('event_date', '<=', $date)
            ->reorder('event_date', 'desc')
            ->orderByDesc('id')
            ->first();

        if ($event) {
            return ((float) $event->ownership_after) / 100;
        }

        return $this->ownershipFraction();
    }

    /** Consolidation elimination journals recorded against this group parent. */
    public function groupEliminations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GroupElimination::class)->latest('elimination_date');
    }

    /** True when this company heads a group (flagged as parent and has subsidiaries). */
    public function isGroupParent(): bool
    {
        return $this->is_consolidation_parent && $this->subsidiaries()->exists();
    }

    public static function classifyRelationship(float $ownershipPct, bool $isJointVenture = false): string
    {
        if ($isJointVenture) return 'joint_venture';
        if ($ownershipPct > 50) return 'subsidiary';
        if ($ownershipPct >= 20) return 'associate';
        return 'investment';
    }

    public function getRelationshipInfoAttribute(): array
    {
        $type = $this->relationship_type
            ?? self::classifyRelationship((float) ($this->group_ownership_percentage ?? 0), (bool) $this->is_joint_venture);
        return self::RELATIONSHIP_TYPES[$type] ?? self::RELATIONSHIP_TYPES['investment'];
    }

    public function getEffectiveRelationshipTypeAttribute(): string
    {
        return $this->relationship_type
            ?? self::classifyRelationship((float) ($this->group_ownership_percentage ?? 0), (bool) $this->is_joint_venture);
    }

    /**
     * Entities forming the consolidated group as at a date: this parent first,
     * then every subsidiary controlled on that date. Used by the consolidation
     * engine to aggregate line by line.
     *
     * @return \Illuminate\Support\Collection<int, Company>
     */
    public function groupEntities(?string $asOfDate = null): \Illuminate\Support\Collection
    {
        $asOfDate ??= now()->toDateString();

        $subs = $this->subsidiaries()->orderBy('registered_name')->get()
            ->filter(fn(Company $s) => $s->isControlledAt($asOfDate))
            ->values();

        return collect([$this])->merge($subs);
    }

    /** The parent's effective holding fraction in this subsidiary (0..1). */
    public function ownershipFraction(): float
    {
        return ((float) ($this->group_ownership_percentage ?? 100)) / 100;
    }

    public function chartOfAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChartOfAccount::class);
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function financialPeriods(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FinancialPeriod::class)->orderBy('start_date');
    }

    /**
     * The financial period whose date range contains $date; if none contains it,
     * the latest period starting on or before it; if all periods start after it,
     * the earliest period. Returns null only when the company has no periods.
     */
    public function periodForDate(string $date): ?FinancialPeriod
    {
        $periods = $this->relationLoaded('financialPeriods')
            ? $this->financialPeriods
            : $this->financialPeriods()->get();

        if ($periods->isEmpty()) {
            return null;
        }

        $containing = $periods->first(fn (FinancialPeriod $p) => $p->containsDate($date));
        if ($containing) {
            return $containing;
        }

        $before = $periods->filter(fn (FinancialPeriod $p) => $p->start_date->toDateString() <= $date)->last();

        return $before ?? $periods->first();
    }

    /**
     * Period-aware opening-balance context for a report as at $date.
     *
     *   ['start' => 'Y-m-d', 'amounts' => [chart_of_account_id => float]]
     *
     * `start` is the start date of the relevant period (movements should be summed
     * from here, not from the beginning of time). `amounts` are the signed opening
     * balances for that period. Returns ['start' => null, 'amounts' => null] when
     * the company has no financial periods — callers then fall back to the legacy
     * chart_of_accounts.opening_balance column and cumulative movements.
     *
     * @return array{start: ?string, amounts: ?array<int,float>}
     */
    public function openingContext(string $date): array
    {
        $period = $this->periodForDate($date);

        if (! $period) {
            return ['start' => null, 'amounts' => null];
        }

        $amounts = $period->openingBalances()
            ->pluck('amount', 'chart_of_account_id')
            ->map(fn ($v) => (float) $v)
            ->all();

        return ['start' => $period->start_date->toDateString(), 'amounts' => $amounts];
    }

    /** First calendar month of the financial year (1–12), derived from the year-end month. */
    public function financialYearStartMonth(): int
    {
        return (($this->financial_year_end_month ?? 12) % 12) + 1;
    }

    /**
     * Start/end dates (Y-m-d) of the financial year that contains $date.
     *
     * @return array{0: string, 1: string}
     */
    public function financialYearBounds(string $date): array
    {
        $ref = \Carbon\Carbon::parse($date);
        $startMonth = $this->financialYearStartMonth();
        $startYear = $ref->month >= $startMonth ? $ref->year : $ref->year - 1;
        $start = \Carbon\Carbon::create($startYear, $startMonth, 1);
        $end = (clone $start)->addYear()->subDay();

        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function deliveryNotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DeliveryNote::class);
    }

    public function creditNotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function customers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function suppliers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function supplierInvoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function purchaseOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function customerAgeAnalyses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerAgeAnalysis::class);
    }

    public function eclRateSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EclRateSetting::class);
    }

    public function inventoryItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function vatRegistrations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VatRegistration::class);
    }

    public function financialStatementNotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FinancialStatementNote::class)->orderBy('sort_order');
    }

    public function resolveChildRouteBinding($childType, $value, $field): ?Model
    {
        if ($childType === 'note') {
            return $this->financialStatementNotes()
                ->where('slug', $value)
                ->firstOrFail();
        }

        return parent::resolveChildRouteBinding($childType, $value, $field);
    }

    public function afsSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AfsSetting::class);
    }

    public function ppeClasses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PpeClass::class)->orderBy('sort_order')->orderBy('name');
    }

    public function assets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function intangibleAssets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(IntangibleAsset::class);
    }

    public function intangibleClasses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(IntangibleClass::class);
    }

    public function assetsHeldForSale(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssetHeldForSale::class);
    }

    public function investmentProperties(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InvestmentProperty::class);
    }

    public function investmentPropertyClasses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InvestmentPropertyClass::class);
    }

    public function biologicalAssets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BiologicalAsset::class);
    }

    public function biologicalAssetClasses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BiologicalAssetClass::class);
    }

    public function leases(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function provisions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Provision::class);
    }

    public function provisionClasses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProvisionClass::class);
    }

    public function relatedParties(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RelatedParty::class);
    }

    public function borrowingCostCapitalisations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BorrowingCostCapitalisation::class);
    }

    public function revenueContracts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RevenueContract::class);
    }

    public function governmentGrants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GovernmentGrant::class);
    }

    public function shareBasedPaymentArrangements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ShareBasedPaymentArrangement::class);
    }

    public function deferredTaxItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DeferredTaxItem::class);
    }

    public function employees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function payrollComponents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PayrollComponent::class)->orderBy('sort_order');
    }

    public function payrollRuns(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PayrollRun::class);
    }

    public function actions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CompanyAction::class);
    }

    public function emailAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CompanyEmailAccount::class);
    }

    public function activeVatRegistration(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(VatRegistration::class)->where('is_active', true)->latestOfMany();
    }

    public function isVatRegistered(): bool
    {
        return $this->vat_number !== null;
    }

    public function isOnboardingComplete(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** @return array<string, string> */
    public static function industries(): array
    {
        return [
            'agriculture'          => 'Agriculture & Farming',
            'automotive'           => 'Automotive & Transport',
            'construction'         => 'Construction & Engineering',
            'education'            => 'Education & Training',
            'financial_services'   => 'Financial Services',
            'food_beverage'        => 'Food & Beverage',
            'healthcare'           => 'Healthcare & Medical',
            'hospitality'          => 'Hospitality & Tourism',
            'information_technology' => 'Information Technology',
            'legal'                => 'Legal Services',
            'manufacturing'        => 'Manufacturing',
            'media_advertising'    => 'Media & Advertising',
            'mining'               => 'Mining & Resources',
            'non_profit'           => 'Non-Profit / NGO',
            'professional_services' => 'Professional Services',
            'real_estate'          => 'Real Estate & Property',
            'retail_wholesale'     => 'Retail & Wholesale',
            'telecommunications'   => 'Telecommunications',
            'other'                => 'Other',
        ];
    }

    /** @return array<string, string> */
    public static function companyTypes(): array
    {
        return [
            'pty_ltd'         => 'Private Company (Pty) Ltd',
            'sole_proprietor' => 'Sole Proprietor',
            'npc'             => 'Non-Profit Company (NPC)',
            'cc'              => 'Close Corporation (CC)',
            'public_ltd'      => 'Public Company (Ltd)',
            'partnership'     => 'Partnership',
            'trust'           => 'Trust',
        ];
    }

    /** @return array<int, string> */
    public static function months(): array
    {
        return [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
    }

    /** @return array<string, string> */
    public static function saProvinces(): array
    {
        return [
            'EC' => 'Eastern Cape',
            'FS' => 'Free State',
            'GP' => 'Gauteng',
            'KZN' => 'KwaZulu-Natal',
            'LP' => 'Limpopo',
            'MP' => 'Mpumalanga',
            'NC' => 'Northern Cape',
            'NW' => 'North West',
            'WC' => 'Western Cape',
        ];
    }

    public function getCompanyTypeLabelAttribute(): string
    {
        return static::companyTypes()[$this->company_type] ?? $this->company_type;
    }

    public function getFinancialYearEndLabelAttribute(): string
    {
        return static::months()[$this->financial_year_end_month] ?? '';
    }
}
