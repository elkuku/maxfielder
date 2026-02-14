<?php

namespace App\Tests\Enum;

use App\Enum\MapBoxStylesEnum;
use PHPUnit\Framework\TestCase;

class MapBoxStylesEnumTest extends TestCase
{
    public function testForSelectReturnsCorrectMapping(): void
    {
        $result = MapBoxStylesEnum::forSelect();

        $expected = [
            'mapbox/standard' => 'Standard',
            'nikp3h/clvm8zx18043s01ph1xmu05dd' => 'Standard_Clear',
            'mapbox/streets-v12' => 'Streets',
            'mapbox/outdoors-v12' => 'Outdoors',
            'mapbox/light-v11' => 'Light',
            'mapbox/dark-v11' => 'Dark',
            'mapbox/satellite-v9' => 'Satellite',
            'mapbox/satellite-streets-v12' => 'Satellite_Streets',
            'mapbox/navigation-day-v1' => 'Navigation_Day',
            'mapbox/navigation-night-v1' => 'Navigation_Night',
        ];

        self::assertSame($expected, $result);
    }

    public function testCaseCount(): void
    {
        self::assertCount(10, MapBoxStylesEnum::cases());
    }
}
