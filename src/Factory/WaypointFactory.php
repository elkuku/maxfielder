<?php

namespace App\Factory;

use App\Entity\Waypoint;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Waypoint>
 */
final class WaypointFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Waypoint::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'guid' => self::faker()->unique()->uuid(),
        ];
    }
}
