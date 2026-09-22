<?php

namespace App\Enums;

enum RequestedQuoteStatus: string
{
    case NEW = 'new';

    case CONTACTED = 'contacted';

    case PROCESSING = 'processing';

    case ACCEPTED = 'accepted';

    case REJECTED = 'rejected';

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Nový',
            self::CONTACTED => 'Kontaktovaný',
            self::PROCESSING => 'Probíhá',
            self::ACCEPTED => 'Přijato',
            self::REJECTED => 'Odmítnutý',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::NEW => 'orange',
            self::CONTACTED => 'blue',
            self::PROCESSING => 'lime',
            self::ACCEPTED => 'green',
            self::REJECTED => 'red',
        };
    }
}
