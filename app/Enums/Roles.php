<?php

namespace App\Enums;

enum Roles: int
{
    case ADMIN = 11;

    case STAKEHOLDER = 21;

    case CUSTOMER = 22;

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::STAKEHOLDER => 'Stakeholder',
            self::CUSTOMER => 'Zákazník',
        };
    }

    public function snake(): string
    {
        return match ($this) {
            self::ADMIN => 'admin',
            self::STAKEHOLDER => 'stakeholder',
            self::CUSTOMER => 'customer',
        };
    }
}
