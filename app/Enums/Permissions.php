<?php

namespace App\Enums;

enum Permissions: int
{
    case QUOTE_VIEW_ALL = 1;

    case QUOTE_VIEW_OWN = 2;

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

    case WORK_EDIT_ALL = 13;

    case WORK_EDIT_OWN = 14;

    case WORK_DELETE_ALL = 15;

    case WORK_DELETE_OWN = 16;

    case WORK_CREATE_ALL = 23;

    case WORK_CREATE_OWN = 24;

    case WORK_RESTORE_ALL = 25;

    case WORK_RESTORE_OWN = 26;

    case WORK_FORCE_DELETE_ALL = 27;

    case WORK_FORCE_DELETE_OWN = 28;

    case FILE_VIEW_ALL = 29;

    case FILE_VIEW_OWN = 30;

    case FILE_CREATE_ALL = 31;

    case FILE_CREATE_OWN = 32;

    case FILE_EDIT_ALL = 33;

    case FILE_EDIT_OWN = 34;

    case FILE_DELETE_ALL = 35;

    case FILE_DELETE_OWN = 36;

    case FILE_FORCE_DELETE_ALL = 37;

    case FILE_FORCE_DELETE_OWN = 38;

    case PDF_GENERATE_ALL = 39;

    case USER_VIEW_ALL = 40;

    case USER_EDIT_ALL = 41;

    case USER_VIEW_OWN = 42;

    case USER_EDIT_OWN = 46;

    case USER_DELETE_ALL = 47;

    case USER_DELETE_OWN = 48;

    case USER_CREATE_ALL = 49;

    case USER_CREATE_OWN = 50;

    case USER_RESTORE_ALL = 51;

    case USER_RESTORE_OWN = 52;

    case USER_FORCE_DELETE_ALL = 53;

    case USER_FORCE_DELETE_OWN = 54;

    case ASSEMBLY_ACTION_VIEW_ALL = 63;

    case ASSEMBLY_ACTION_CREATE_ALL = 65;

    case ASSEMBLY_ACTION_EDIT_ALL = 66;

    case ASSEMBLY_ACTION_DELETE_ALL = 67;

    case ASSEMBLY_ACTION_RESTORE_ALL = 68;

    case ASSEMBLY_ACTION_FORCE_DELETE_ALL = 69;

    case SPARE_PART_VIEW_ALL = 55;

    case SPARE_PART_CREATE_ALL = 57;

    case SPARE_PART_EDIT_ALL = 58;

    case SPARE_PART_DELETE_ALL = 59;

    case SPARE_PART_RESTORE_ALL = 60;

    case SPARE_PART_FORCE_DELETE_ALL = 61;

    case ROLE_VIEW_ALL = 70;

    case ROLE_EDIT_ALL = 71;

    case PERMISSION_VIEW_ALL = 72;

    public static function fromLabel(string $label): ?self
    {
        foreach (self::cases() as $permission) {
            if ($permission->label() === $label) {
                return $permission;
            }
        }

        return null;
    }

    public function scope(): string
    {
        return match (true) {
            str_ends_with($this->name, '_ALL') => 'all',
            str_ends_with($this->name, '_OWN') => 'own',
            default => 'other',
        };
    }

    public function modelType(): string
    {
        return match (true) {
            str_starts_with($this->name, 'ASSEMBLY_ACTION') => 'assembly actions',
            str_starts_with($this->name, 'SPARE_PART') => 'spare parts',
            str_starts_with($this->name, 'USER') => 'users',
            str_starts_with($this->name, 'FILE') => 'files',
            str_starts_with($this->name, 'QUOTE') => 'quotes',
            str_starts_with($this->name, 'WORK') => 'works',
            str_starts_with($this->name, 'PDF') => 'pdfs',
            str_starts_with($this->name, 'ROLE_') => 'roles',
            str_starts_with($this->name, 'PERMISSION_') => 'permissions',
            default => mb_strtolower(explode('_', $this->name, 2)[0]),
        };
    }

    public function badgeLabel(): string
    {
        $label = $this->label();
        $suffix = ' ' . $this->modelType();

        return str_ends_with($label, $suffix)
            ? mb_substr($label, 0, -mb_strlen($suffix))
            : $label;
    }

    public function label(): string
    {
        return match ($this) {
            self::ROLE_VIEW_ALL => 'View all roles',
            self::ROLE_EDIT_ALL => 'Edit all roles',
            self::PERMISSION_VIEW_ALL => 'View all permissions',

            self::ASSEMBLY_ACTION_VIEW_ALL => 'View all assembly actions',
            self::ASSEMBLY_ACTION_CREATE_ALL => 'Create all assembly actions',
            self::ASSEMBLY_ACTION_EDIT_ALL => 'Edit all assembly actions',
            self::ASSEMBLY_ACTION_DELETE_ALL => 'Delete all assembly actions',
            self::ASSEMBLY_ACTION_RESTORE_ALL => 'Restore all assembly actions',
            self::ASSEMBLY_ACTION_FORCE_DELETE_ALL => 'Force delete all assembly actions',

            self::SPARE_PART_VIEW_ALL => 'View all spare parts',
            self::SPARE_PART_CREATE_ALL => 'Create all spare parts',
            self::SPARE_PART_EDIT_ALL => 'Edit all spare parts',
            self::SPARE_PART_DELETE_ALL => 'Delete all spare parts',
            self::SPARE_PART_RESTORE_ALL => 'Restore all spare parts',
            self::SPARE_PART_FORCE_DELETE_ALL => 'Force delete all spare parts',

            self::USER_VIEW_ALL => 'View all users',
            self::USER_EDIT_ALL => 'Edit all users',
            self::USER_VIEW_OWN => 'View own users',
            self::USER_EDIT_OWN => 'Edit own users',
            self::USER_DELETE_ALL => 'Delete all users',
            self::USER_DELETE_OWN => 'Delete own users',
            self::USER_CREATE_ALL => 'Create all users',
            self::USER_CREATE_OWN => 'Create own users',
            self::USER_RESTORE_ALL => 'Restore all users',
            self::USER_RESTORE_OWN => 'Restore own users',
            self::USER_FORCE_DELETE_ALL => 'Force delete all users',
            self::USER_FORCE_DELETE_OWN => 'Force delete own users',

            self::PDF_GENERATE_ALL => 'Generate PDF',

            self::FILE_VIEW_ALL => 'View all files',
            self::FILE_VIEW_OWN => 'View own files',
            self::FILE_CREATE_ALL => 'Create all files',
            self::FILE_CREATE_OWN => 'Create own files',
            self::FILE_EDIT_ALL => 'Edit all files',
            self::FILE_EDIT_OWN => 'Edit own files',
            self::FILE_DELETE_ALL => 'Delete all files',
            self::FILE_DELETE_OWN => 'Delete own files',
            self::FILE_FORCE_DELETE_ALL => 'Force delete all files',
            self::FILE_FORCE_DELETE_OWN => 'Force delete own files',

            self::QUOTE_VIEW_ALL => 'View all quotes',
            self::QUOTE_VIEW_OWN => 'View own quotes',
            self::QUOTE_EDIT_ALL => 'Edit all quotes',
            self::QUOTE_EDIT_OWN => 'Edit own quotes',
            self::QUOTE_DELETE_ALL => 'Delete all quotes',
            self::QUOTE_DELETE_OWN => 'Delete own quotes',
            self::QUOTE_CREATE_ALL => 'Create all quotes',
            self::QUOTE_CREATE_OWN => 'Create own quotes',
            self::QUOTE_RESTORE_ALL => 'Restore all quotes',
            self::QUOTE_RESTORE_OWN => 'Restore own quotes',
            self::QUOTE_FORCE_DELETE_ALL => 'Force delete all quotes',
            self::QUOTE_FORCE_DELETE_OWN => 'Force delete own quotes',

            self::WORK_VIEW_ALL => 'View all works',
            self::WORK_VIEW_OWN => 'View own works',
            self::WORK_EDIT_ALL => 'Edit all works',
            self::WORK_EDIT_OWN => 'Edit own works',
            self::WORK_DELETE_ALL => 'Delete all works',
            self::WORK_DELETE_OWN => 'Delete own works',
            self::WORK_CREATE_ALL => 'Create all works',
            self::WORK_CREATE_OWN => 'Create own works',
            self::WORK_RESTORE_ALL => 'Restore all works',
            self::WORK_RESTORE_OWN => 'Restore own works',
            self::WORK_FORCE_DELETE_ALL => 'Force delete all works',
            self::WORK_FORCE_DELETE_OWN => 'Force delete own works',
        };
    }
}
