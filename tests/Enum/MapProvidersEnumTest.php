<?php

namespace App\Tests\Enum;

use App\Enum\MapProvidersEnum;
use PHPUnit\Framework\TestCase;

class MapProvidersEnumTest extends TestCase
{
    public function testForSelectReturnsCorrectMapping(): void
    {
        $result = MapProvidersEnum::forSelect();

        $expected = [
            'leaflet' => 'leaflet',
            'mapbox' => 'mapbox',
        ];

        self::assertSame($expected, $result);
    }

    public function testCaseValues(): void
    {
        self::assertSame('leaflet', MapProvidersEnum::leaflet->value);
        self::assertSame('mapbox', MapProvidersEnum::mapbox->value);
    }
}
