<?php

namespace App\Tests\Controller;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserAccessTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testUserLogin(): void
    {
        $client = static::createClient();

        $client->request(Request::METHOD_GET, '/');

        self::assertResponseIsSuccessful();

        $user = UserFactory::createOne(['identifier' => 'user']);

        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h4', 'Welcome user');
    }
}
