<?php

namespace App\Tests\Service;

use App\Entity\Waypoint;
use App\Service\MaxFieldGenerator;
use PHPUnit\Framework\TestCase;

class MaxFieldGeneratorTest extends TestCase
{
    private MaxFieldGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new MaxFieldGenerator(
            '/tmp/test-project',
            '/usr/bin/maxfield',
            4,
            '',
            '',
            'test-container',
            'https://intel.ingress.com/intel',
        );
    }

    public function testConvertWayPointsToMaxFields(): void
    {
        $wp1 = new Waypoint();
        $wp1->setName('Portal Alpha');
        $wp1->setLat(48.123);
        $wp1->setLon(11.456);

        $wp2 = new Waypoint();
        $wp2->setName('Portal Beta');
        $wp2->setLat(49.789);
        $wp2->setLon(12.321);

        $result = $this->generator->convertWayPointsToMaxFields([$wp1, $wp2]);

        $lines = explode("\n", $result);
        self::assertCount(2, $lines);
        self::assertStringContainsString('Portal Alpha', $lines[0]);
        self::assertStringContainsString('48.123,11.456', $lines[0]);
        self::assertStringContainsString('https://intel.ingress.com/intel', $lines[0]);
        self::assertStringContainsString('Portal Beta', $lines[1]);
    }

    public function testConvertWayPointsStripsSpecialChars(): void
    {
        $wp = new Waypoint();
        $wp->setName('Portal; With# Special');
        $wp->setLat(1.0);
        $wp->setLon(2.0);

        $result = $this->generator->convertWayPointsToMaxFields([$wp]);

        self::assertStringContainsString('Portal With Special', $result);
        self::assertStringNotContainsString(';', explode(';', $result)[0]);
    }

    public function testGetWaypointsMap(): void
    {
        $wp1 = new Waypoint();
        $wp1->setName('Portal A');
        $wp1->setGuid('guid-a');

        $wp2 = new Waypoint();
        $wp2->setName('Portal B');
        $wp2->setGuid('guid-b');

        $result = $this->generator->getWaypointsMap([$wp1, $wp2]);

        self::assertCount(2, $result);
        self::assertSame(0, $result[0][0]);
        self::assertNull($result[0][1]);
        self::assertSame('guid-a', $result[0][2]);
        self::assertSame('Portal A', $result[0][3]);
        self::assertSame(1, $result[1][0]);
        self::assertSame('Portal B', $result[1][3]);
    }

    public function testGetWaypointsMapStripsSpecialChars(): void
    {
        $wp = new Waypoint();
        $wp->setName('Portal; With# Comma,');
        $wp->setGuid('guid');

        $result = $this->generator->getWaypointsMap([$wp]);

        self::assertSame('Portal With Comma', $result[0][3]);
    }

    public function testGetImagePath(): void
    {
        self::assertSame(
            '/tmp/test-project/public/maxfields/my-field/link_map.png',
            $this->generator->getImagePath('my-field', 'link_map.png')
        );
    }
}
