<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueContractAccountLink extends Model
{
    public const ROLE_REVENUE            = 'revenue';
    public const ROLE_CONTRACT_ASSET     = 'contract_asset';
    public const ROLE_CONTRACT_LIABILITY = 'contract_liability';
    public const ROLE_TRADE_RECEIVABLE   = 'trade_receivable';
    public const ROLE_COST_OF_CONTRACT   = 'cost_of_contract';
    public const ROLE_FINANCING          = 'financing_component';

    protected $fillable = [
        'company_id',
        'chart_of_account_id',
        'role',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public static function roles(): array
    {
        return [
            self::ROLE_REVENUE            => 'Revenue',
            self::ROLE_CONTRACT_ASSET     => 'Contract Asset',
            self::ROLE_CONTRACT_LIABILITY => 'Contract Liability',
            self::ROLE_TRADE_RECEIVABLE   => 'Trade Receivable',
            self::ROLE_COST_OF_CONTRACT   => 'Cost of Contract',
            self::ROLE_FINANCING          => 'Financing Component',
        ];
    }
}
