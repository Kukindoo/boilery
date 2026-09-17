<?php

namespace App\Enums;

enum FileTypes: string
{
    case UNKNOWN = 'unknown';

    case BOILER_LABEL = 'boiler-label';

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
            self::UNKNOWN => 'Neznámý',
            self::BOILER_LABEL => 'Štítek',
            self::RECEIPT => 'Kupní smlouva',
            self::WARRANTY => 'Záruka',
        };
    }

    public function snake(): string
    {
        return match ($this) {
            self::UNKNOWN => 'neznamy',
            self::BOILER_LABEL => 'stitek',
            self::RECEIPT => 'kupni_smlouva',
            self::WARRANTY => 'zaruka',
        };
    }
}