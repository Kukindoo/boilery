<?php

namespace App\Enums;

enum IssueStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Otevřený',
            self::CLOSED => 'Uzavřený',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::OPEN => 'green',
            self::CLOSED => 'zinc',
        };
    }
}
