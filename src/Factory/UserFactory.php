<?php

namespace App\Factory;

use App\Entity\User;
use App\Enum\UserRole;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return User::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'identifier' => self::faker()->unique()->userName(),
        ];
    }

    public function asAdmin(): self
    {
        return $this->with(['role' => UserRole::ADMIN]);
    }
}
