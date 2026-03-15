<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use UnexpectedValueException;
use App\Enum\UserRole;
use App\Factory\MaxfieldFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class MaxFieldsControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testIndexRedirectsForAnonymousUser(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/maxfield/list');

        self::assertResponseRedirects();
    }

    public function testIndexRendersForAuthenticatedUser(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/list');

        self::assertResponseIsSuccessful();
    }

    public function testIndexWithListLgPartial(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/list', ['partial' => 'list_lg']);

        self::assertResponseIsSuccessful();
    }

    public function testIndexWithListSmPartial(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/list', ['partial' => 'list_sm']);

        self::assertResponseIsSuccessful();
    }

    public function testIndexWithInvalidPartialThrows(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $client->loginUser($user);
        $client->catchExceptions(false);

        $this->expectException(UnexpectedValueException::class);
        $client->request(Request::METHOD_GET, '/maxfield/list', ['partial' => 'invalid']);
    }

    public function testCheckRequiresAdmin(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/check');

        self::assertResponseStatusCodeSame(403);
    }

    public function testCheckRendersForAdmin(): void
    {
        $client = self::createClient();
        $admin = UserFactory::createOne(['role' => UserRole::ADMIN]);
        $client->loginUser($admin);

        $client->request(Request::METHOD_GET, '/maxfield/check');

        self::assertResponseIsSuccessful();
    }

    public function testStatusReturnsJsonForAuthenticatedUser(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $user, 'path' => 'nonexistent-path']);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/status/'.$maxfield->getId());

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
    }

    public function testToggleFavouriteAddsToFavourites(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $user]);
        $client->loginUser($user);

        $client->request(
            Request::METHOD_GET,
            '/maxfield/toggle-favourite/'.$maxfield->getId(),
            server: ['HTTP_X-Requested-With' => 'XMLHttpRequest'],
        );

        self::assertResponseIsSuccessful();
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('new-state', $data);
    }

    public function testDeleteRequiresOwnership(): void
    {
        $client = self::createClient();
        $owner = UserFactory::createOne();
        $other = UserFactory::createOne();
        $maxfield = MaxfieldFactory::createOne(['owner' => $owner]);
        $client->loginUser($other);

        $client->request(Request::METHOD_GET, '/maxfield/delete/'.$maxfield->getId());

        self::assertResponseStatusCodeSame(403);
    }
}
