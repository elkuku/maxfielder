<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\MapProvidersEnum;
use PHPUnit\Framework\TestCase;

final class MapProvidersEnumTest extends TestCase
{
    public function testForSelectReturnsCorrectMapping(): void
    {
        $result = MapProvidersEnum::forSelect();

        $expected = [
            'leaflet' => 'leaflet',
            'mapbox' => 'mapbox',
        ];

        $this->assertSame($expected, $result);
    }

    public function testCaseValues(): void
    {
        $this->assertSame('leaflet', MapProvidersEnum::leaflet->value);
        $this->assertSame('mapbox', MapProvidersEnum::mapbox->value);
    }
}
