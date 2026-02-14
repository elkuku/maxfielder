<?php

namespace App\Tests\Enum;

use App\Enum\MapBoxProfilesEnum;
use PHPUnit\Framework\TestCase;

class ForSelectTraitTest extends TestCase
{
    public function testForSelectReturnsValueKeyedByName(): void
    {
        $result = MapBoxProfilesEnum::forSelect();

        $expected = [
            'mapbox/driving' => 'Driving',
            'mapbox/walking' => 'Walking',
            'mapbox/cycling' => 'Cycling',
        ];

        self::assertSame($expected, $result);
    }
}
