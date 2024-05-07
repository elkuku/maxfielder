<?php

namespace App\Settings;

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
}