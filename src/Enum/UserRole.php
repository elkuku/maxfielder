<?php

namespace App\Enum;

enum UserRole: string
{
    case USER = 'ROLE_USER';
    case AGENT = 'ROLE_AGENT';
    case ADMIN = 'ROLE_ADMIN';

    public function cssClass(): string
    {
        return match ($this) {
            self::USER => 'badge bg-secondary',
            self::AGENT => 'badge bg-secondaryx',
            self::ADMIN => 'badge bg-danger',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::AGENT => 'Agent',
            self::USER => 'User',
        };
    }
}
