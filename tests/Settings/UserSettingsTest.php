<?php

namespace App\Tests\Settings;

use App\Enum\MapBoxProfilesEnum;
use App\Enum\MapBoxStylesEnum;
use App\Enum\MapProvidersEnum;
use App\Settings\UserSettings;
use PHPUnit\Framework\TestCase;

class UserSettingsTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $settings = new UserSettings();

        self::assertSame('', $settings->agentName);
        self::assertSame(0.0, $settings->lat);
        self::assertSame(0.0, $settings->lon);
        self::assertSame(0, $settings->zoom);
        self::assertSame('', $settings->mapboxApiKey);
    }

    public function testDefaultEnumValues(): void
    {
        $settings = new UserSettings();

        self::assertSame(MapBoxStylesEnum::Standard, $settings->defaultStyle);
        self::assertSame(MapBoxProfilesEnum::Driving, $settings->defaultProfile);
        self::assertSame(MapProvidersEnum::leaflet, $settings->mapProvider);
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

        self::assertSame('Agent007', $settings->agentName);
        self::assertSame(48.123, $settings->lat);
        self::assertSame(11.456, $settings->lon);
        self::assertSame(15, $settings->zoom);
        self::assertSame(MapBoxStylesEnum::Dark, $settings->defaultStyle);
        self::assertSame(MapBoxProfilesEnum::Walking, $settings->defaultProfile);
        self::assertSame(MapProvidersEnum::mapbox, $settings->mapProvider);
        self::assertSame('pk.test123', $settings->mapboxApiKey);
    }
}
