<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProvisionClassAccountLink extends Model
{
    public const ROLE_PROVISION_LIABILITY = 'provision_liability';
    public const ROLE_PROVISION_EXPENSE   = 'provision_expense';
    public const ROLE_UNWINDING_EXPENSE   = 'unwinding_expense';
    public const ROLE_UTILISATION_BANK    = 'utilisation_bank';
    public const ROLE_REVERSAL_INCOME     = 'reversal_income';

    protected $fillable = [
        'provision_class_id',
        'chart_of_account_id',
        'role',
    ];

    public function provisionClass(): BelongsTo
    {
        return $this->belongsTo(ProvisionClass::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    /** @return array<string, string> */
    public static function roles(): array
    {
        return [
            self::ROLE_PROVISION_LIABILITY => 'Provision Liability',
            self::ROLE_PROVISION_EXPENSE   => 'Provision Expense',
            self::ROLE_UNWINDING_EXPENSE   => 'Unwinding Expense (Finance Cost)',
            self::ROLE_UTILISATION_BANK    => 'Utilisation (Bank / Payable)',
            self::ROLE_REVERSAL_INCOME     => 'Reversal Income',
        ];
    }
}
