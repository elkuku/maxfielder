<?php

declare(strict_types=1);

namespace App\Tests\Parser;

use UnexpectedValueException;
use App\Entity\Waypoint;
use App\Parser\AbstractParser;
use App\Service\WayPointHelper;
use PHPUnit\Framework\TestCase;

final class AbstractParserTest extends TestCase
{
    public function testSupportsReturnsTrueWhenKeyExistsWithTruthyValue(): void
    {
        $parser = $this->createConcreteParser('mytype');

        $this->assertTrue($parser->supports(['mytype' => 'some data']));
    }

    public function testSupportsReturnsFalseWhenKeyMissing(): void
    {
        $parser = $this->createConcreteParser('mytype');

        $this->assertFalse($parser->supports(['othertype' => 'data']));
    }

    public function testSupportsReturnsFalseWhenValueFalsy(): void
    {
        $parser = $this->createConcreteParser('mytype');

        $this->assertFalse($parser->supports(['mytype' => '']));
    }

    public function testSupportsReturnsFalseWhenValueIsZero(): void
    {
        $parser = $this->createConcreteParser('mytype');

        /** @phpstan-ignore argument.type */
        $this->assertFalse($parser->supports(['mytype' => 0]));
    }

    public function testSupportsThrowsWhenTypeIsEmpty(): void
    {
        $parser = $this->createConcreteParser('');

        $this->expectException(UnexpectedValueException::class);
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

        $this->assertInstanceOf(Waypoint::class, $waypoint);
        $this->assertSame('guid-123', $waypoint->getGuid());
        $this->assertEqualsWithDelta(48.123, $waypoint->getLat(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(11.456, $waypoint->getLon(), PHP_FLOAT_EPSILON);
        $this->assertSame('Portal Name', $waypoint->getName());
        $this->assertSame('http://example.com/image.jpg', $waypoint->getImage());
    }

    private function createConcreteParser(string $type): ConcreteTestParser
    {
        $helper = $this->createStub(WayPointHelper::class);

        return new ConcreteTestParser($helper, $type);
    }
}

/**
 * @internal
 */
class ConcreteTestParser extends AbstractParser
{
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
    ): Waypoint {
        return $this->createWayPoint($guid, $lat, $lon, $name, $image);
    }
}
