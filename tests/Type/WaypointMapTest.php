<?php

namespace App\Tests\Type;

use App\Type\WaypointMap;
use PHPUnit\Framework\TestCase;

class WaypointMapTest extends TestCase
{
    public function testPropertyAssignment(): void
    {
        $waypointMap = new WaypointMap();
        $waypointMap->mapNo = 5;
        $waypointMap->dbId = 42;
        $waypointMap->guid = 'abc-123';
        $waypointMap->name = 'Test Portal';

        self::assertSame(5, $waypointMap->mapNo);
        self::assertSame(42, $waypointMap->dbId);
        self::assertSame('abc-123', $waypointMap->guid);
        self::assertSame('Test Portal', $waypointMap->name);
    }
}
