<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft    => 'Draft',
            self::Sent     => 'Sent',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::Expired  => 'Expired',
        };
    }

    /**
     * Standard quotation state-transition matrix.
     *
     * Draft    → Sent (issue to client)
     * Sent     → Accepted, Declined, Expired (client response or timeout)
     * Accepted → terminal (convert to invoice via convertToInvoice action)
     * Declined → Sent (resubmit revised), Draft (revise before resending)
     * Expired  → Sent (resubmit), Draft (revise)
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft    => [self::Sent],
            self::Sent     => [self::Accepted, self::Declined, self::Expired],
            self::Accepted => [],
            self::Declined => [self::Draft, self::Sent],
            self::Expired  => [self::Draft, self::Sent],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return $status === $this || in_array($status, $this->allowedTransitions(), true);
    }
}
