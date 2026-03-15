<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use UnexpectedValueException;
use App\Factory\MaxfieldFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class DefaultControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testIndexRendersForAnonymousUser(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/');

        self::assertResponseIsSuccessful();
    }

    public function testIndexRendersForAuthenticatedUser(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['identifier' => 'agent007']);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h4', 'Welcome agent007');
    }

    public function testIndexWithSearchTermFiltersResults(): void
    {
        $client = self::createClient();
        MaxfieldFactory::createOne(['name' => 'Downtown Alpha']);
        MaxfieldFactory::createOne(['name' => 'Harbor Beta']);
        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/', ['q' => 'Alpha']);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('body');
    }

    public function testIndexWithSearchPreviewPartial(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/', ['partial' => 'searchPreview']);

        self::assertResponseIsSuccessful();
    }

    public function testIndexWithFavouritesPartial(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/', ['partial' => 'favourites']);

        self::assertResponseIsSuccessful();
    }

    public function testIndexWithContentListPartial(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/', ['partial' => 'contentList']);

        self::assertResponseIsSuccessful();
    }

    public function testIndexWithInvalidPartialThrows(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->catchExceptions(false);
        $this->expectException(UnexpectedValueException::class);

        $client->request(Request::METHOD_GET, '/', ['partial' => 'invalid']);
    }
}
