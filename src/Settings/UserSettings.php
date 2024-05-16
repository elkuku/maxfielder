<?php

namespace App\Settings;

use App\Enum\MapBoxProfilesEnum;
use App\Enum\MapBoxStylesEnum;

class UserSettings
{
    public string $agentName = '';

    public float $lat = 0;

    public float $lon = 0;

    public int $zoom = 0;

    public MapBoxStylesEnum $defaultStyle = MapBoxStylesEnum::Standard;

    public MapBoxProfilesEnum $defaultProfile = MapBoxProfilesEnum::Driving;
}