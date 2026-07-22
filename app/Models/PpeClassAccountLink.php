<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpeClassAccountLink extends Model
{
    public const ROLE_COST                     = 'cost';
    public const ROLE_ACCUMULATED_DEPRECIATION = 'accumulated_depreciation';
    public const ROLE_DEPRECIATION_EXPENSE     = 'depreciation_expense';
    public const ROLE_DISPOSAL                 = 'disposal';
    public const ROLE_REVALUATION              = 'revaluation';           // OCI equity — Revaluation Surplus
    public const ROLE_IMPAIRMENT_LOSS          = 'impairment_loss';       // P&L expense (IAS 36)
    public const ROLE_ACCUMULATED_IMPAIRMENT   = 'accumulated_impairment'; // contra-asset (IAS 36)
    public const ROLE_IMPAIRMENT_REVERSAL      = 'impairment_reversal';   // P&L income (IAS 36.114)
    public const ROLE_DEFERRED_TAX_LIABILITY   = 'deferred_tax_liability';
    public const ROLE_DEFERRED_TAX_EXPENSE     = 'deferred_tax_expense';

    protected $fillable = [
        'ppe_class_id',
        'chart_of_account_id',
        'role',
    ];

    public function ppeClass(): BelongsTo
    {
        return $this->belongsTo(PpeClass::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    /** @return array<string, string> */
    public static function roles(): array
    {
        return [
            self::ROLE_COST                     => 'Cost',
            self::ROLE_ACCUMULATED_DEPRECIATION => 'Accumulated Depreciation',
            self::ROLE_DEPRECIATION_EXPENSE     => 'Depreciation Expense',
            self::ROLE_DISPOSAL                 => 'Disposal / Proceeds',
            self::ROLE_REVALUATION              => 'Revaluation Surplus (OCI)',
            self::ROLE_IMPAIRMENT_LOSS          => 'Impairment Loss (P&L)',
            self::ROLE_ACCUMULATED_IMPAIRMENT   => 'Accumulated Impairment',
            self::ROLE_IMPAIRMENT_REVERSAL      => 'Impairment Reversal (P&L)',
            self::ROLE_DEFERRED_TAX_LIABILITY   => 'Deferred Tax Liability',
            self::ROLE_DEFERRED_TAX_EXPENSE     => 'Deferred Tax Expense',
        ];
    }

    /** Roles that indicate disposal activity — controls schedule row visibility. */
    public static function disposalRoles(): array
    {
        return [self::ROLE_DISPOSAL, self::ROLE_REVALUATION];
    }
}
