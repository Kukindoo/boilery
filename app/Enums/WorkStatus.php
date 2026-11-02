<?php

namespace App\Enums;

enum WorkStatus: string
{
    case NEW = 'new';

    case PROCESSING = 'processing';

    case FINISHED = 'finished';

    case FAILED = 'failed';

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
            self::PROCESSING => 'Probíhá',
            self::FINISHED => 'Dokončeno',
            self::FAILED => 'Selhalo',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::NEW => 'orange',
            self::PROCESSING => 'lime',
            self::FINISHED => 'green',
            self::FAILED => 'red',
        };
    }
}
