<?php

namespace App\Tests\Entity;

use App\Entity\Waypoint;
use PHPUnit\Framework\TestCase;

class WaypointTest extends TestCase
{
    public function testToStringReturnsName(): void
    {
        $waypoint = new Waypoint();
        $waypoint->setName('Test Portal');

        self::assertSame('Test Portal', (string)$waypoint);
    }

    public function testGetSetName(): void
    {
        $waypoint = new Waypoint();

        self::assertSame('', $waypoint->getName());

        $result = $waypoint->setName('My Portal');

        self::assertSame('My Portal', $waypoint->getName());
        self::assertSame($waypoint, $result);
    }

    public function testGetSetLat(): void
    {
        $waypoint = new Waypoint();

        self::assertSame(0.0, $waypoint->getLat());

        $waypoint->setLat(48.123456);
        self::assertSame(48.123456, $waypoint->getLat());
    }

    public function testGetSetLon(): void
    {
        $waypoint = new Waypoint();

        self::assertSame(0.0, $waypoint->getLon());

        $waypoint->setLon(11.654321);
        self::assertSame(11.654321, $waypoint->getLon());
    }

    public function testGetSetGuid(): void
    {
        $waypoint = new Waypoint();

        self::assertSame('', $waypoint->getGuid());

        $waypoint->setGuid('abc123.def456');
        self::assertSame('abc123.def456', $waypoint->getGuid());
    }

    public function testGetSetImage(): void
    {
        $waypoint = new Waypoint();

        self::assertNull($waypoint->getImage());

        $result = $waypoint->setImage('https://example.com/image.jpg');

        self::assertSame('https://example.com/image.jpg', $waypoint->getImage());
        self::assertSame($waypoint, $result);
    }

    public function testSetImageNull(): void
    {
        $waypoint = new Waypoint();
        $waypoint->setImage('some-image.jpg');
        $waypoint->setImage(null);

        self::assertNull($waypoint->getImage());
    }

    public function testIdIsNullByDefault(): void
    {
        $waypoint = new Waypoint();

        self::assertNull($waypoint->getId());
    }
}
