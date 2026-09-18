<?php

namespace App\Enums;

enum BoilerManufacturers: string
{
    case UNKNOWN = 'unknown';

    case ARISTON = 'ariston';

    case DRAZICE = 'drazice';

    case STIEBEL = 'stiebel-eltron';

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
            self::ARISTON => 'Ariston',
            self::DRAZICE => 'Dražice',
            self::STIEBEL => 'Stiebel Eltron',
        };
    }
}