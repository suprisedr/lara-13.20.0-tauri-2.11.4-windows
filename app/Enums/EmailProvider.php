<?php

namespace App\Enums;

enum EmailProvider: string
{
    case Gmail = 'gmail';
    case Outlook = 'outlook';
    case Imap = 'imap';
    case Pop = 'pop';

    public function label(): string
    {
        return match ($this) {
            self::Gmail => 'Gmail',
            self::Outlook => 'Outlook',
            self::Imap => 'IMAP',
            self::Pop => 'POP',
        };
    }

    public function usesOAuth(): bool
    {
        return in_array($this, [self::Gmail, self::Outlook]);
    }

    public function defaultImapHost(): ?string
    {
        return match ($this) {
            self::Gmail => 'imap.gmail.com',
            self::Outlook => 'outlook.office365.com',
            default => null,
        };
    }

    public function defaultImapPort(): int
    {
        return 993;
    }
}
