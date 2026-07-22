<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Voided = 'voided';
    case WriteOff = 'write_off';

    public function label(): string
    {
        return match ($this) {
            self::Draft        => 'Draft',
            self::Pending      => 'Pending',
            self::PartiallyPaid => 'Partially Paid',
            self::Paid         => 'Paid',
            self::Overdue      => 'Overdue',
            self::Voided       => 'Voided',
            self::WriteOff     => 'Written Off',
        };
    }

    /**
     * Standard invoice state-transition matrix.
     *
     * Draft      → Pending (issue), Voided (cancel before sending)
     * Pending    → PartiallyPaid, Paid, Overdue, Voided
     * PartiallyPaid → Paid, Overdue, Voided
     * Overdue    → Pending (cured), PartiallyPaid, Paid, Voided, WriteOff
     * Paid       → terminal
     * Voided     → terminal
     * WriteOff   → terminal
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft         => [self::Pending, self::Voided],
            self::Pending       => [self::PartiallyPaid, self::Paid, self::Overdue, self::Voided],
            self::PartiallyPaid => [self::Paid, self::Overdue, self::Voided],
            self::Overdue       => [self::Pending, self::PartiallyPaid, self::Paid, self::Voided, self::WriteOff],
            self::Paid          => [],
            self::Voided        => [],
            self::WriteOff      => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return $status === $this || in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Statuses that mean the invoice's journal entry should be posted by the
     * AI agent (if it hasn't been already).
     */
    public function triggersAiPosting(): bool
    {
        return in_array($this, [self::Pending, self::PartiallyPaid, self::Paid, self::Overdue], true);
    }

    /**
     * Statuses that represent an invoice that has been issued to the client
     * and is an active receivable (as opposed to draft, voided or written off).
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::PartiallyPaid, self::Overdue], true);
    }
}
