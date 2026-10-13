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

    case QUOTE_DELETE_ALL = 7;

    case QUOTE_DELETE_OWN = 8;

    case QUOTE_CREATE_ALL = 17;

    case QUOTE_CREATE_OWN = 22;

    case QUOTE_RESTORE_ALL = 18;

    case QUOTE_RESTORE_OWN = 19;

    case QUOTE_FORCE_DELETE_ALL = 20;

    case QUOTE_FORCE_DELETE_OWN = 21;

    case WORK_VIEW_ALL = 9;

    case WORK_VIEW_OWN = 10;

    case WORK_VIEW_INDEX = 11;

    case WORK_PRINT_ALL = 12;

    case WORK_EDIT_ALL = 13;

    case WORK_EDIT_OWN = 14;

    case WORK_DELETE_ALL = 15;

    case WORK_DELETE_OWN = 16;

    public function label(): string
    {
        return match ($this) {
            self::QUOTE_VIEW_ALL => 'View all quotes',
            self::QUOTE_VIEW_OWN => 'View own quotes',
            self::QUOTE_VIEW_INDEX => 'View index quotes',
            self::QUOTE_PRINT_ALL => 'Print all quotes',
            self::QUOTE_EDIT_ALL => 'Edit all quotes',
            self::QUOTE_EDIT_OWN => 'Edit own quotes',
            self::QUOTE_DELETE_ALL => 'Delete all quotes',
            self::QUOTE_DELETE_OWN => 'Delete own quotes',
            self::WORK_VIEW_ALL => 'View all works',
            self::WORK_VIEW_OWN => 'View own works',
            self::WORK_VIEW_INDEX => 'View index works',
            self::WORK_PRINT_ALL => 'Print all works',
            self::WORK_EDIT_ALL => 'Edit all works',
            self::WORK_EDIT_OWN => 'Edit own works',
            self::WORK_DELETE_ALL => 'Delete all works',
            self::WORK_DELETE_OWN => 'Delete own works',
            self::QUOTE_CREATE_ALL => 'Create all quotes',
            self::QUOTE_CREATE_OWN => 'Create own quotes',
            self::QUOTE_RESTORE_ALL => 'Restore all quotes',
            self::QUOTE_RESTORE_OWN => 'Restore own quotes',
            self::QUOTE_FORCE_DELETE_ALL => 'Force delete all quotes',
            self::QUOTE_FORCE_DELETE_OWN => 'Force delete own quotes',
        };
    }
}
