<?php

namespace App\Factory;

use App\Entity\Maxfield;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Maxfield>
 */
final class MaxfieldFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Maxfield::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->word(),
            'path' => self::faker()->slug(),
            'owner' => UserFactory::new(),
        ];
    }
}
