<?php

namespace App\Tests\Enum;

use App\Enum\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function testCssClass(): void
    {
        self::assertSame('badge bg-secondary', UserRole::USER->cssClass());
        self::assertSame('badge bg-secondaryx', UserRole::AGENT->cssClass());
        self::assertSame('badge bg-danger', UserRole::ADMIN->cssClass());
    }

    public function testLabel(): void
    {
        self::assertSame('User', UserRole::USER->label());
        self::assertSame('Agent', UserRole::AGENT->label());
        self::assertSame('Administrator', UserRole::ADMIN->label());
    }

    public function testValues(): void
    {
        self::assertSame('ROLE_USER', UserRole::USER->value);
        self::assertSame('ROLE_AGENT', UserRole::AGENT->value);
        self::assertSame('ROLE_ADMIN', UserRole::ADMIN->value);
    }
}
