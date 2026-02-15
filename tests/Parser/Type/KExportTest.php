<?php

declare(strict_types=1);

namespace App\Tests\Parser\Type;

use UnexpectedValueException;
use App\Parser\Type\KExport;
use App\Service\WayPointHelper;
use PHPUnit\Framework\TestCase;

final class KExportTest extends TestCase
{
    public function testParseValidJson(): void
    {
        $parser = new KExport($this->createStub(WayPointHelper::class));

        $items = [
            [
                'guid' => 'abc-123',
                'title' => 'Portal Alpha',
                'lat' => 48.123,
                'lng' => 11.456,
                'image' => 'http://example.com/img.jpg',
            ],
            [
                'guid' => 'def-456',
                'title' => 'Portal Beta',
                'lat' => 49.789,
                'lng' => 12.321,
                'image' => '',
            ],
        ];

        $waypoints = $parser->parse(['kexport' => (string) json_encode($items)]);

        $this->assertCount(2, $waypoints);
        $this->assertSame('abc-123', $waypoints[0]->getGuid());
        $this->assertSame('Portal Alpha', $waypoints[0]->getName());
        $this->assertEqualsWithDelta(48.123, $waypoints[0]->getLat(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(11.456, $waypoints[0]->getLon(), PHP_FLOAT_EPSILON);
        $this->assertSame('http://example.com/img.jpg', $waypoints[0]->getImage());
        $this->assertSame('def-456', $waypoints[1]->getGuid());
        $this->assertSame('Portal Beta', $waypoints[1]->getName());
    }

    public function testParseInvalidJsonThrows(): void
    {
        $parser = new KExport($this->createStub(WayPointHelper::class));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid KExport JSON data');
        $parser->parse(['kexport' => '{invalid json']);
    }

    public function testParseMissingGuidSkipsItem(): void
    {
        $parser = new KExport($this->createStub(WayPointHelper::class));

        $items = [
            ['guid' => '', 'title' => 'No Guid', 'lat' => 1.0, 'lng' => 2.0],
            ['guid' => 'valid-guid', 'title' => 'Has Guid', 'lat' => 3.0, 'lng' => 4.0],
        ];

        $waypoints = $parser->parse(['kexport' => (string) json_encode($items)]);

        $this->assertCount(1, $waypoints);
        $this->assertSame('valid-guid', $waypoints[0]->getGuid());
    }

    public function testParseMissingTitleSkipsItem(): void
    {
        $parser = new KExport($this->createStub(WayPointHelper::class));

        $items = [
            ['guid' => 'guid-1', 'title' => '', 'lat' => 1.0, 'lng' => 2.0],
            ['guid' => 'guid-2', 'title' => 'Valid', 'lat' => 3.0, 'lng' => 4.0],
        ];

        $waypoints = $parser->parse(['kexport' => (string) json_encode($items)]);

        $this->assertCount(1, $waypoints);
        $this->assertSame('guid-2', $waypoints[0]->getGuid());
    }

    public function testParseWithImportImagesCallsCheckImage(): void
    {
        $helper = $this->createMock(WayPointHelper::class);
        $helper->expects($this->once())
            ->method('checkImage')
            ->with('guid-1', 'http://example.com/image.jpg');

        $parser = new KExport($helper);

        $items = [
            [
                'guid' => 'guid-1',
                'title' => 'Portal',
                'lat' => 1.0,
                'lng' => 2.0,
                'image' => 'http://example.com/image.jpg',
            ],
        ];

        $parser->parse([
            'kexport' => (string) json_encode($items),
            'importImages' => '1',
        ]);
    }

    public function testParseWithoutImportImagesDoesNotCallCheckImage(): void
    {
        $helper = $this->createMock(WayPointHelper::class);
        $helper->expects($this->never())->method('checkImage');

        $parser = new KExport($helper);

        $items = [
            [
                'guid' => 'guid-1',
                'title' => 'Portal',
                'lat' => 1.0,
                'lng' => 2.0,
                'image' => 'http://example.com/image.jpg',
            ],
        ];

        $parser->parse(['kexport' => (string) json_encode($items)]);
    }

    public function testSupportsKexportData(): void
    {
        $parser = new KExport($this->createStub(WayPointHelper::class));

        $this->assertTrue($parser->supports(['kexport' => 'data']));
        $this->assertFalse($parser->supports(['multiexportjson' => 'data']));
        $this->assertFalse($parser->supports(['kexport' => '']));
    }
}
