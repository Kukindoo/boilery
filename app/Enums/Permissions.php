<?php

namespace App\Enums;

enum Permissions: int
{
    case QUOTE_VIEW_ALL = 1;

    case QUOTE_VIEW_OWN = 2;

    case QUOTE_VIEW_INDEX = 3;

    case QUOTE_PRINT_ALL = 4;

    case QUOTE_EDIT_ALL = 5;

    case QUOTE_EDIT_OWN = 6;

    public function label(): string
    {
        return match ($this) {
            self::QUOTE_VIEW_ALL => 'View all quotes',
            self::QUOTE_VIEW_OWN => 'View own quotes',
            self::QUOTE_VIEW_INDEX => 'View index quotes',
            self::QUOTE_PRINT_ALL => 'Print all quotes',
            self::QUOTE_EDIT_ALL => 'Edit all quotes',
            self::QUOTE_EDIT_OWN => 'Edit own quotes',
        };
    }
}
