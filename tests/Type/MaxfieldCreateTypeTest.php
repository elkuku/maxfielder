<?php

namespace App\Tests\Type;

use App\Type\MaxfieldCreateType;
use PHPUnit\Framework\TestCase;

class MaxfieldCreateTypeTest extends TestCase
{
    public function testGetPoints(): void
    {
        $type = new MaxfieldCreateType();
        $type->points = '1,2,3';

        self::assertSame([1, 2, 3], $type->getPoints());
    }

    public function testGetPointsSingle(): void
    {
        $type = new MaxfieldCreateType();
        $type->points = '42';

        self::assertSame([42], $type->getPoints());
    }

    public function testGetPlayersNumWithValue(): void
    {
        $type = new MaxfieldCreateType();
        $type->playersNum = 3;

        self::assertSame(3, $type->getPlayersNum());
    }

    public function testGetPlayersNumDefaultsToOne(): void
    {
        $type = new MaxfieldCreateType();

        self::assertSame(1, $type->getPlayersNum());
    }

    public function testGetPlayersNumZeroDefaultsToOne(): void
    {
        $type = new MaxfieldCreateType();
        $type->playersNum = 0;

        self::assertSame(1, $type->getPlayersNum());
    }

    public function testGetProjectNameContainsSlugifiedBuildName(): void
    {
        $type = new MaxfieldCreateType();
        $type->buildName = 'My Test Build';

        $projectName = $type->getProjectName();

        self::assertStringContainsString('My-Test-Build', $projectName);
        self::assertMatchesRegularExpression('/^[a-f0-9]+-My-Test-Build$/', $projectName);
    }
}
