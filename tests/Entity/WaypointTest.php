<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Waypoint;
use PHPUnit\Framework\TestCase;

final class WaypointTest extends TestCase
{
    public function testToStringReturnsName(): void
    {
        $waypoint = new Waypoint();
        $waypoint->setName('Test Portal');

        $this->assertSame('Test Portal', (string)$waypoint);
    }

    public function testGetSetName(): void
    {
        $waypoint = new Waypoint();

        $this->assertSame('', $waypoint->getName());

        $result = $waypoint->setName('My Portal');

        $this->assertSame('My Portal', $waypoint->getName());
        $this->assertSame($waypoint, $result);
    }

    public function testGetSetLat(): void
    {
        $waypoint = new Waypoint();

        $this->assertEqualsWithDelta(0.0, $waypoint->getLat(), PHP_FLOAT_EPSILON);

        $waypoint->setLat(48.123456);
        $this->assertEqualsWithDelta(48.123456, $waypoint->getLat(), PHP_FLOAT_EPSILON);
    }

    public function testGetSetLon(): void
    {
        $waypoint = new Waypoint();

        $this->assertEqualsWithDelta(0.0, $waypoint->getLon(), PHP_FLOAT_EPSILON);

        $waypoint->setLon(11.654321);
        $this->assertEqualsWithDelta(11.654321, $waypoint->getLon(), PHP_FLOAT_EPSILON);
    }

    public function testGetSetGuid(): void
    {
        $waypoint = new Waypoint();

        $this->assertSame('', $waypoint->getGuid());

        $waypoint->setGuid('abc123.def456');
        $this->assertSame('abc123.def456', $waypoint->getGuid());
    }

    public function testGetSetImage(): void
    {
        $waypoint = new Waypoint();

        $this->assertNull($waypoint->getImage());

        $result = $waypoint->setImage('https://example.com/image.jpg');

        $this->assertSame('https://example.com/image.jpg', $waypoint->getImage());
        $this->assertSame($waypoint, $result);
    }

    public function testSetImageNull(): void
    {
        $waypoint = new Waypoint();
        $waypoint->setImage('some-image.jpg');
        $waypoint->setImage(null);

        $this->assertNull($waypoint->getImage());
    }

    public function testIdIsNullByDefault(): void
    {
        $waypoint = new Waypoint();

        $this->assertNull($waypoint->getId());
    }
}
