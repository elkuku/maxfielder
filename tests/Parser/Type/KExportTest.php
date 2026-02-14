<?php

namespace App\Tests\Parser\Type;

use App\Parser\Type\KExport;
use App\Service\WayPointHelper;
use PHPUnit\Framework\TestCase;

class KExportTest extends TestCase
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

        $waypoints = $parser->parse(['kexport' => json_encode($items)]);

        self::assertCount(2, $waypoints);
        self::assertSame('abc-123', $waypoints[0]->getGuid());
        self::assertSame('Portal Alpha', $waypoints[0]->getName());
        self::assertSame(48.123, $waypoints[0]->getLat());
        self::assertSame(11.456, $waypoints[0]->getLon());
        self::assertSame('http://example.com/img.jpg', $waypoints[0]->getImage());
        self::assertSame('def-456', $waypoints[1]->getGuid());
        self::assertSame('Portal Beta', $waypoints[1]->getName());
    }

    public function testParseInvalidJsonThrows(): void
    {
        $parser = new KExport($this->createStub(WayPointHelper::class));

        $this->expectException(\UnexpectedValueException::class);
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

        $waypoints = $parser->parse(['kexport' => json_encode($items)]);

        self::assertCount(1, $waypoints);
        self::assertSame('valid-guid', $waypoints[0]->getGuid());
    }

    public function testParseMissingTitleSkipsItem(): void
    {
        $parser = new KExport($this->createStub(WayPointHelper::class));

        $items = [
            ['guid' => 'guid-1', 'title' => '', 'lat' => 1.0, 'lng' => 2.0],
            ['guid' => 'guid-2', 'title' => 'Valid', 'lat' => 3.0, 'lng' => 4.0],
        ];

        $waypoints = $parser->parse(['kexport' => json_encode($items)]);

        self::assertCount(1, $waypoints);
        self::assertSame('guid-2', $waypoints[0]->getGuid());
    }

    public function testParseWithImportImagesCallsCheckImage(): void
    {
        $helper = $this->createMock(WayPointHelper::class);
        $helper->expects(self::once())
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
            'kexport' => json_encode($items),
            'importImages' => '1',
        ]);
    }

    public function testParseWithoutImportImagesDoesNotCallCheckImage(): void
    {
        $helper = $this->createMock(WayPointHelper::class);
        $helper->expects(self::never())->method('checkImage');

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

        $parser->parse(['kexport' => json_encode($items)]);
    }

    public function testSupportsKexportData(): void
    {
        $parser = new KExport($this->createStub(WayPointHelper::class));

        self::assertTrue($parser->supports(['kexport' => 'data']));
        self::assertFalse($parser->supports(['multiexportjson' => 'data']));
        self::assertFalse($parser->supports(['kexport' => '']));
    }
}
