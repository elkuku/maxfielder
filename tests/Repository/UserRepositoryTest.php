<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Enum\UserRole;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class UserRepositoryTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private UserRepository $repo;

    protected function setUp(): void
    {
        /** @var UserRepository $repo */
        $repo = self::getContainer()->get(UserRepository::class);
        $this->repo = $repo;
    }

    public function testFindUsersReturnsOnlyUserRole(): void
    {
        UserFactory::createOne(['identifier' => 'user1', 'role' => UserRole::USER]);
        UserFactory::createOne(['identifier' => 'user2', 'role' => UserRole::USER]);
        UserFactory::createOne(['identifier' => 'admin1', 'role' => UserRole::ADMIN]);

        $result = $this->repo->findUsers();

        $this->assertCount(2, $result);
        foreach ($result as $user) {
            $this->assertContains(UserRole::USER->value, $user->getRoles());
        }
    }

    public function testFindUsersReturnsEmptyWhenNoUsers(): void
    {
        UserFactory::createOne(['role' => UserRole::ADMIN]);

        $result = $this->repo->findUsers();

        $this->assertSame([], $result);
    }

    public function testFindUsersOrderedById(): void
    {
        $u1 = UserFactory::createOne(['identifier' => 'aaa', 'role' => UserRole::USER]);
        $u2 = UserFactory::createOne(['identifier' => 'bbb', 'role' => UserRole::USER]);

        $result = $this->repo->findUsers();

        $this->assertSame($u1->getId(), $result[0]->getId());
        $this->assertSame($u2->getId(), $result[1]->getId());
    }
}
