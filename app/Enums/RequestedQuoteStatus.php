<?php

namespace App\Enums;

enum RequestedQuoteStatus: string
{
    case UNKNOWN = 'unknown';

    case NEW = 'new';

    case CONTACTED = 'contacted';

    case SCHEDULED = 'scheduled';

    case PROCESSING = 'processing';

    case DONE = 'done';

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::UNKNOWN => 'Neznámý',
            self::NEW => 'Nový',
            self::CONTACTED => 'Kontaktovaný',
            self::SCHEDULED => 'Domluvený',
            self::PROCESSING => 'Probíhá',
            self::DONE => 'Hotovo',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::UNKNOWN => 'slate-400',
            self::NEW => 'orange-300',
            self::CONTACTED => 'yellow-300',
            self::SCHEDULED => 'lime-600',
            self::PROCESSING => 'lime-600',
            self::DONE => 'green-600',
        };
    }
}
