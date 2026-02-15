<?php

declare(strict_types=1);

namespace App\Tests\Settings;

use App\Enum\MapBoxProfilesEnum;
use App\Enum\MapBoxStylesEnum;
use App\Enum\MapProvidersEnum;
use App\Settings\UserSettings;
use PHPUnit\Framework\TestCase;

final class UserSettingsTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $settings = new UserSettings();

        $this->assertSame('', $settings->agentName);
        $this->assertEqualsWithDelta(0.0, $settings->lat, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $settings->lon, PHP_FLOAT_EPSILON);
        $this->assertSame(0, $settings->zoom);
        $this->assertSame('', $settings->mapboxApiKey);
    }

    public function testDefaultEnumValues(): void
    {
        $settings = new UserSettings();

        $this->assertSame(MapBoxStylesEnum::Standard, $settings->defaultStyle);
        $this->assertSame(MapBoxProfilesEnum::Driving, $settings->defaultProfile);
        $this->assertSame(MapProvidersEnum::leaflet, $settings->mapProvider);
    }

    public function testPropertyAssignment(): void
    {
        $settings = new UserSettings();
        $settings->agentName = 'Agent007';
        $settings->lat = 48.123;
        $settings->lon = 11.456;
        $settings->zoom = 15;
        $settings->defaultStyle = MapBoxStylesEnum::Dark;
        $settings->defaultProfile = MapBoxProfilesEnum::Walking;
        $settings->mapProvider = MapProvidersEnum::mapbox;
        $settings->mapboxApiKey = 'pk.test123';

        $this->assertSame('Agent007', $settings->agentName);
        $this->assertEqualsWithDelta(48.123, $settings->lat, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(11.456, $settings->lon, PHP_FLOAT_EPSILON);
        $this->assertSame(15, $settings->zoom);
        $this->assertSame(MapBoxStylesEnum::Dark, $settings->defaultStyle);
        $this->assertSame(MapBoxProfilesEnum::Walking, $settings->defaultProfile);
        $this->assertSame(MapProvidersEnum::mapbox, $settings->mapProvider);
        $this->assertSame('pk.test123', $settings->mapboxApiKey);
    }
}
