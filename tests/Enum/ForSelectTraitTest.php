<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\MapBoxProfilesEnum;
use PHPUnit\Framework\TestCase;

final class ForSelectTraitTest extends TestCase
{
    public function testForSelectReturnsValueKeyedByName(): void
    {
        $result = MapBoxProfilesEnum::forSelect();

        $expected = [
            'mapbox/driving' => 'Driving',
            'mapbox/walking' => 'Walking',
            'mapbox/cycling' => 'Cycling',
        ];

        $this->assertSame($expected, $result);
    }
}
