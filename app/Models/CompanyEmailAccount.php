<?php

namespace App\Models;

use App\Enums\EmailProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyEmailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'provider',
        'email_address',
        'oauth_token',
        'oauth_refresh_token',
        'oauth_expires_at',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'imap_username',
        'imap_password',
        'is_active',
        'last_synced_at',
        'last_error',
        'sync_interval_minutes',
    ];

    protected function casts(): array
    {
        return [
            'provider' => EmailProvider::class,
            'oauth_expires_at' => 'datetime',
            'imap_password' => 'encrypted',
            'oauth_token' => 'encrypted',
            'oauth_refresh_token' => 'encrypted',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'sync_interval_minutes' => 'integer',
        ];
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplierEmails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupplierEmail::class);
    }

    public function isOAuthExpired(): bool
    {
        if (!$this->oauth_expires_at) {
            return true;
        }

        return $this->oauth_expires_at->isPast();
    }

    public function getEffectiveImapHost(): string
    {
        return $this->imap_host ?? $this->provider->defaultImapHost() ?? '';
    }

    public function getEffectiveImapPort(): int
    {
        return $this->imap_port ?? $this->provider->defaultImapPort();
    }
}
