<?php

namespace App\Enums;

enum SupplierEmailStatus: string
{
    case PendingReview = 'pending_review';
    case Matched = 'matched';
    case NewSupplier = 'new_supplier';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'Pending Review',
            self::Matched => 'Matched',
            self::NewSupplier => 'New Supplier',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PendingReview => 'yellow',
            self::Matched => 'green',
            self::NewSupplier => 'blue',
        };
    }
}
