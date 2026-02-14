<?php

namespace App\Tests\Parser;

use App\Entity\Waypoint;
use App\Parser\AbstractParser;
use App\Service\WayPointHelper;
use PHPUnit\Framework\TestCase;

class AbstractParserTest extends TestCase
{
    public function testSupportsReturnsTrueWhenKeyExistsWithTruthyValue(): void
    {
        $parser = $this->createConcreteParser('mytype');

        self::assertTrue($parser->supports(['mytype' => 'some data']));
    }

    public function testSupportsReturnsFalseWhenKeyMissing(): void
    {
        $parser = $this->createConcreteParser('mytype');

        self::assertFalse($parser->supports(['othertype' => 'data']));
    }

    public function testSupportsReturnsFalseWhenValueFalsy(): void
    {
        $parser = $this->createConcreteParser('mytype');

        self::assertFalse($parser->supports(['mytype' => '']));
    }

    public function testSupportsReturnsFalseWhenValueIsZero(): void
    {
        $parser = $this->createConcreteParser('mytype');

        self::assertFalse($parser->supports(['mytype' => 0]));
    }

    public function testSupportsThrowsWhenTypeIsEmpty(): void
    {
        $parser = $this->createConcreteParser('');

        $this->expectException(\UnexpectedValueException::class);
        $parser->supports(['anything' => 'data']);
    }

    public function testCreateWayPointReturnsCorrectWaypoint(): void
    {
        $parser = $this->createConcreteParser('test');

        $waypoint = $parser->callCreateWayPoint(
            'guid-123',
            48.123,
            11.456,
            'Portal Name',
            'http://example.com/image.jpg'
        );

        self::assertInstanceOf(Waypoint::class, $waypoint);
        self::assertSame('guid-123', $waypoint->getGuid());
        self::assertSame(48.123, $waypoint->getLat());
        self::assertSame(11.456, $waypoint->getLon());
        self::assertSame('Portal Name', $waypoint->getName());
        self::assertSame('http://example.com/image.jpg', $waypoint->getImage());
    }

    private function createConcreteParser(string $type): object
    {
        $helper = $this->createStub(WayPointHelper::class);

        return new class($helper, $type) extends AbstractParser {
            public function __construct(
                WayPointHelper $wayPointHelper,
                private readonly string $type,
            ) {
                parent::__construct($wayPointHelper);
            }

            protected function getType(): string
            {
                return $this->type;
            }

            public function parse(array $data): array
            {
                return [];
            }

            public function callCreateWayPoint(
                string $guid,
                float $lat,
                float $lon,
                string $name,
                string $image,
            ): \App\Entity\Waypoint {
                return $this->createWayPoint($guid, $lat, $lon, $name, $image);
            }
        };
    }
}
