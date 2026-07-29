<?php

namespace App\Models;

use App\Jobs\EmbedPendingTransactionsJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;
use App\Models\Concerns\BroadcastsChanges;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory, Searchable;
    use BroadcastsChanges;

    protected $fillable = [
        'company_id',
        'user_id',
        'transaction_date',
        'description',
        'reference',
        'status',
        'notes',
        'source_document',
        'reversal_of_id',
        'corrects_id',
        'is_embedded',
        'embedded_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'is_embedded' => 'boolean',
            'embedded_at' => 'datetime',
        ];
    }

    public function scopeNotEmbedded($query)
    {
        return $query->where('is_embedded', false);
    }

    // ── Meilisearch / Scout ───────────────────────────────────────

    public function searchableAs(): string
    {
        return 'transactions';
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing('journalLines.account');

        $lineTexts = $this->journalLines->map(function ($line) {
            return implode(' ', array_filter([
                $line->description,
                $line->account?->account_name,
                $line->account?->account_code,
                $line->amount !== null ? number_format((float) $line->amount, 2) : null,
            ]));
        })->implode(' ');

        return [
            'id'               => $this->id,
            'company_id'       => $this->company_id,
            'description'      => $this->description ?? '',
            'reference'        => $this->reference ?? '',
            'status'           => $this->status ?? '',
            'transaction_date' => $this->transaction_date?->format('Y-m-d') ?? '',
            'transaction_date_timestamp' => $this->transaction_date?->timestamp ?? 0,
            'notes'            => $this->notes ?? '',
            'line_text'        => $lineTexts,
            'total_debit'      => (float) $this->journalLines->where('type', 'debit')->sum('amount'),
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return true;
    }

    protected static function booted(): void
    {
        static::created(function (Transaction $tx): void {
            if (! config('services.gemini.api_key')) {
                return;
            }
            Cache::put(
                EmbedPendingTransactionsJob::LAST_SEEN_KEY,
                now()->timestamp,
                now()->addMinutes(30),
            );
            EmbedPendingTransactionsJob::dispatch()
                ->delay(now()->addSeconds(EmbedPendingTransactionsJob::QUIET_WINDOW_SECONDS));
        });

        static::deleted(function (Transaction $tx): void {
            // Keep the pgvector store in lockstep with the MySQL ledger.
            try {
                \Illuminate\Support\Facades\DB::connection('pgsql')
                    ->table('transaction_vectors')
                    ->where('mysql_transaction_id', $tx->id)
                    ->delete();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning(
                    'Failed to purge transaction_vectors row for transaction '.$tx->id.': '.$e->getMessage()
                );
            }
        });
    }

    /**
     * Total monetary value of the entry (sum of debit lines).
     * Transactions in this ledger are journal-entry headers; the amount
     * lives across journal_lines.
     */
    public function embeddableAmount(): float
    {
        return (float) $this->journalLines()->where('type', 'debit')->sum('amount');
    }

    public function embeddableCounterparty(): ?string
    {
        return $this->company?->name;
    }

    public function embeddableCurrency(): string
    {
        return $this->company?->currency ?? 'ZAR';
    }

    public function toEmbeddableText(): string
    {
        $amount = number_format($this->embeddableAmount(), 2, '.', '');
        $currency = $this->embeddableCurrency();
        $party = $this->embeddableCounterparty() ?? '-';
        $ref = $this->reference ?? '-';
        $date = optional($this->transaction_date)->format('Y-m-d') ?? '-';
        $desc = $this->description ?? '-';

        return "amount:{$amount} currency:{$currency} party:{$party} ref:{$ref} date:{$date} desc:{$desc}";
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /** The transaction this entry reverses (set on the reversal transaction). */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'reversal_of_id');
    }

    /** The reversal transaction that was created to undo this entry. */
    public function reversal(): HasOne
    {
        return $this->hasOne(Transaction::class, 'reversal_of_id');
    }

    /** The correction draft created after this transaction was reversed. */
    public function correction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'corrects_id');
    }

    /** The original transaction this entry is a correction of. */
    public function corrects(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'corrects_id');
    }

    public function isReversed(): bool
    {
        return $this->reversal !== null;
    }

    public function isReversal(): bool
    {
        return $this->reversal_of_id !== null;
    }

    public function isCorrection(): bool
    {
        return $this->corrects_id !== null;
    }

    public function isImmutable(): bool
    {
        return $this->status === 'reversed' || $this->isReversed() || $this->isReversal();
    }

    /**
     * Sum of all debit lines on this transaction.
     */
    public function getTotalDebitsAttribute(): float
    {
        return (float) $this->journalLines->where('type', 'debit')->sum('amount');
    }

    /**
     * Sum of all credit lines on this transaction.
     */
    public function getTotalCreditsAttribute(): float
    {
        return (float) $this->journalLines->where('type', 'credit')->sum('amount');
    }
}
