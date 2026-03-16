<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Enum\UserRole;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class MapControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testEditRendersForAdmin(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::ADMIN]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/map/edit');

        self::assertResponseIsSuccessful();
    }

    public function testEditForbidsNonAdmin(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/map/edit');

        self::assertResponseStatusCodeSame(403);
    }
}
