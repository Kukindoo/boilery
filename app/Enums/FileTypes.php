<?php

namespace App\Enums;

enum FileTypes: string
{
    case SERIAL_NUMBER = 'serial_number';

    case RECEIPT = 'receipt';

    case WARRANTY = 'warranty';

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::SERIAL_NUMBER => 'Štítek',
            self::RECEIPT => 'Kupní smlouva',
            self::WARRANTY => 'Záruka',
        };
    }
}