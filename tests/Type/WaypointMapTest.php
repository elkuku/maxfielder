<?php

declare(strict_types=1);

namespace App\Tests\Type;

use App\Type\WaypointMap;
use PHPUnit\Framework\TestCase;

final class WaypointMapTest extends TestCase
{
    public function testPropertyAssignment(): void
    {
        $waypointMap = new WaypointMap();
        $waypointMap->mapNo = 5;
        $waypointMap->dbId = 42;
        $waypointMap->guid = 'abc-123';
        $waypointMap->name = 'Test Portal';

        $this->assertSame(5, $waypointMap->mapNo);
        $this->assertSame(42, $waypointMap->dbId);
        $this->assertSame('abc-123', $waypointMap->guid);
        $this->assertSame('Test Portal', $waypointMap->name);
    }
}
