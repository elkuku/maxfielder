<?php

namespace App\Tests\Parser;

use App\Parser\WayPointParser;
use App\Service\WayPointHelper;
use PHPUnit\Framework\TestCase;

class WayPointParserTest extends TestCase
{
    public function testParseWithKExportData(): void
    {
        $parser = new WayPointParser($this->createStub(WayPointHelper::class));

        $items = [
            [
                'guid' => 'abc-123',
                'title' => 'Portal Alpha',
                'lat' => 48.123,
                'lng' => 11.456,
                'image' => '',
            ],
        ];

        $waypoints = $parser->parse(['kexport' => (string) json_encode($items)]);

        self::assertCount(1, $waypoints);
        self::assertSame('abc-123', $waypoints[0]->getGuid());
        self::assertSame('Portal Alpha', $waypoints[0]->getName());
    }

    public function testParseThrowsForUnsupportedData(): void
    {
        $parser = new WayPointParser($this->createStub(WayPointHelper::class));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('No suitable parser found');

        $parser->parse(['unsupported_format' => 'data']);
    }
}
