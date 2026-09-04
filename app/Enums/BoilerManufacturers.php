<?php

namespace App\Enums;

enum BoilerManufacturers: string
{
    case ARISTON = 'ariston';

    case DRAZICE = 'drazice';

    case STIEBLE = 'stieble-eltron';

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::ARISTON => 'Ariston',
            self::DRAZICE => 'Dražice',
            self::STIEBLE => 'Stiebl Eltron',
        };
    }
}