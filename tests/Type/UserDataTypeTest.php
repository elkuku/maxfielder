<?php

namespace App\Tests\Type;

use App\Type\UserDataType;
use PHPUnit\Framework\TestCase;

class UserDataTypeTest extends TestCase
{
    public function testDefaultUserId(): void
    {
        $userData = new UserDataType();

        self::assertSame(0, $userData->userId);
    }

    public function testPropertyAssignment(): void
    {
        $userData = new UserDataType();
        $userData->userId = 42;

        self::assertSame(42, $userData->userId);
    }
}
