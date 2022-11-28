<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserAccessTest extends WebTestCase
{
    public function testUserLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        // self::assertSelectorTextContains('h2', 'Hello DefaultController!');

        /**
         * @var UserRepository $userRepository
         */
        $userRepository = static::getContainer()->get(UserRepository::class);

        /**
         * @var \Symfony\Component\Security\Core\User\UserInterface $user
         */
        $user = $userRepository->findOneBy(['identifier' => 'user']);

        $client->loginUser($user);

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        // self::assertSelectorTextContains('h4', 'Welcome user');
    }
}
