<?php

declare(strict_types=1);

namespace App\Tests\Type;

use App\Type\UserDataType;
use PHPUnit\Framework\TestCase;

final class UserDataTypeTest extends TestCase
{
    public function testDefaultUserId(): void
    {
        $userData = new UserDataType();

        $this->assertSame(0, $userData->userId);
    }

    public function testPropertyAssignment(): void
    {
        $userData = new UserDataType();
        $userData->userId = 42;

        $this->assertSame(42, $userData->userId);
    }
}
