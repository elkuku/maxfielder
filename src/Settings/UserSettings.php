<?php

namespace App\Settings;

use App\Enum\MapBoxProfilesEnum;
use App\Enum\MapBoxStylesEnum;
use App\Service\UserSettingsStorageAdapter;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;

#[Settings(storageAdapter: UserSettingsStorageAdapter::class)]
class UserSettings
{
    #[SettingsParameter(label: 'Agent Name', description: 'Your Ingress agent name')]
    public string $agentName = '';

    #[SettingsParameter]
    public float $lat = 0;

    #[SettingsParameter]
    public float $lon = 0;

    #[SettingsParameter]
    public int $zoom = 0;

    #[SettingsParameter(label: 'Default Map Style')]
    public MapBoxStylesEnum $defaultStyle = MapBoxStylesEnum::Standard;

    #[SettingsParameter(label: 'Default Navigation Profile')]
    public MapBoxProfilesEnum $defaultProfile = MapBoxProfilesEnum::Driving;
}