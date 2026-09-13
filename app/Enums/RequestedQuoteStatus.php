<?php

namespace App\Enums;

enum RequestedQuoteStatus: string
{
    case NEW = 'new';

    case CONTACTED = 'contacted';

    case PROCESSING = 'processing';

    case DONE = 'done';

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
            self::DONE => 'Vyřešený',
            self::REJECTED => 'Odmítnutý',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::NEW => 'orange-300',
            self::CONTACTED => 'yellow-300',
            self::PROCESSING => 'lime-600',
            self::DONE => 'green-600',
            self::REJECTED => 'red-400',
        };
    }
}
