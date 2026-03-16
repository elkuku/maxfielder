<?php

declare(strict_types=1);

namespace App\Tests\Command;

use ReflectionMethod;
use App\Command\UserAdminCommand;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class UserAdminCommandTest extends TestCase
{
    private UserAdminCommand $command;

    protected function setUp(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $repo = $this->createStub(UserRepository::class);
        $this->command = new UserAdminCommand($em, $repo);
    }

    public function testGetRolesReturnsAllUserRoleCases(): void
    {
        // Use reflection to call protected method
        $ref = new ReflectionMethod($this->command, 'getRoles');
        $roles = $ref->invoke($this->command);

        $this->assertSame(UserRole::cases(), $roles);
    }

    public function testSetRoleAssignsRoleToUser(): void
    {
        $user = new User();
        $ref = new ReflectionMethod($this->command, 'setRole');
        $result = $ref->invoke($this->command, $user, UserRole::ADMIN->value);

        $this->assertContains(UserRole::ADMIN->value, $result->getRoles());
    }
}
