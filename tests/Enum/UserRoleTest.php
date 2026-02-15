<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\UserRole;
use PHPUnit\Framework\TestCase;

final class UserRoleTest extends TestCase
{
    public function testCssClass(): void
    {
        $this->assertSame('badge bg-secondary', UserRole::USER->cssClass());
        $this->assertSame('badge bg-secondaryx', UserRole::AGENT->cssClass());
        $this->assertSame('badge bg-danger', UserRole::ADMIN->cssClass());
    }

    public function testLabel(): void
    {
        $this->assertSame('User', UserRole::USER->label());
        $this->assertSame('Agent', UserRole::AGENT->label());
        $this->assertSame('Administrator', UserRole::ADMIN->label());
    }

    public function testValues(): void
    {
        $this->assertSame('ROLE_USER', UserRole::USER->value);
        $this->assertSame('ROLE_AGENT', UserRole::AGENT->value);
        $this->assertSame('ROLE_ADMIN', UserRole::ADMIN->value);
    }
}
