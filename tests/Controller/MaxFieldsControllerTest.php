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

    public function testDeleteByOwnerRedirects(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $user, 'path' => 'no-such-dir']);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/delete/'.$maxfield->getId());

        self::assertResponseRedirects();
    }

    public function testViewStatusRendersForAgent(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $user]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/view-status/'.$maxfield->getId());

        self::assertResponseIsSuccessful();
    }

    public function testPlanRendersForAgent(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/plan');

        self::assertResponseIsSuccessful();
    }

    public function testPlan2RendersForAgent(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/plan2');

        self::assertResponseIsSuccessful();
    }

    public function testEditRequiresOwnership(): void
    {
        $client = self::createClient();
        $owner = UserFactory::createOne(['role' => UserRole::AGENT]);
        $other = UserFactory::createOne(['role' => UserRole::AGENT]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $owner]);
        $client->loginUser($other);

        $client->request(Request::METHOD_GET, '/maxfield/edit/'.$maxfield->getId());

        self::assertResponseStatusCodeSame(403);
    }

    public function testEditRendersFormForOwner(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $user]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/edit/'.$maxfield->getId());

        self::assertResponseIsSuccessful();
    }

    public function testDeleteFilesRequiresAdmin(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/delete-files/some-item');

        self::assertResponseStatusCodeSame(403);
    }

    public function testDeleteFilesForAdminRedirects(): void
    {
        $client = self::createClient();
        $admin = UserFactory::createOne(['role' => UserRole::ADMIN]);
        $client->loginUser($admin);

        $client->request(Request::METHOD_GET, '/maxfield/delete-files/no-such-item');

        self::assertResponseRedirects();
    }

    public function testClearUserDataReturnsJson(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $user]);
        $client->loginUser($user);

        $client->request(
            Request::METHOD_POST,
            '/maxfield/clear-user-data/'.$maxfield->getPath(),
            content: json_encode(['agentNum' => 1]) ?: '',
        );

        self::assertResponseIsSuccessful();
        /** @var array<string, string> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('cleared', $data['result']);
    }

    public function testSubmitUserDataCurrentPoint(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $user]);
        $client->loginUser($user);

        $client->request(
            Request::METHOD_POST,
            '/maxfield/submit-user-data/'.$maxfield->getPath(),
            content: json_encode(['agentNum' => 1, 'current_point' => '5']) ?: '',
        );

        self::assertResponseIsSuccessful();
    }

    public function testGetUserDataReturnsEmptyArray(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $user]);
        $client->loginUser($user);

        $client->request(
            Request::METHOD_POST,
            '/maxfield/get-user-data/'.$maxfield->getPath(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['userId' => 1]) ?: '',
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
    }

    public function testEditSubmitsFormAndRedirects(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $user, 'name' => 'Old Name']);
        $client->loginUser($user);

        $crawler = $client->request(Request::METHOD_GET, '/maxfield/edit/'.$maxfield->getId());
        $form = $crawler->filter('form')->form();
        $client->submit($form, ['maxfield_form[name]' => 'Updated Name']);

        self::assertResponseRedirects('/maxfield/list');
    }

    public function testEditWithPartialRendersFormPartial(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $user]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/edit/'.$maxfield->getId(), ['partial' => '1']);

        self::assertResponseIsSuccessful();
    }

    public function testDeleteWithRefererRedirectsToReferer(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $user, 'path' => 'no-such-dir']);
        $client->loginUser($user);

        $client->request(
            Request::METHOD_GET,
            '/maxfield/delete/'.$maxfield->getId(),
            server: ['HTTP_REFERER' => 'http://localhost/maxfield/list'],
        );

        self::assertResponseRedirects('/maxfield/list');
    }

    public function testSubmitUserDataWithFarmDone(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $user]);
        $client->loginUser($user);

        $client->request(
            Request::METHOD_POST,
            '/maxfield/submit-user-data/'.$maxfield->getPath(),
            content: json_encode(['agentNum' => 1, 'farm_done' => [1, 2]]) ?: '',
        );

        self::assertResponseIsSuccessful();
    }

    public function testGetUserDataReturnsDataWhenExists(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $user, 'userData' => [1 => ['current_point' => '5']]]);
        $client->loginUser($user);

        $client->request(
            Request::METHOD_POST,
            '/maxfield/get-user-data/'.$maxfield->getPath(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['userId' => 1]) ?: '',
        );

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('5', $data['current_point']);
    }

    public function testPlayRendersMapboxTemplateForMapboxUser(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne([
            'role' => UserRole::AGENT,
            'params' => ['mapProvider' => 'mapbox', 'mapboxApiKey' => 'pk.test123'],
        ]);
        $maxfield = MaxfieldFactory::createOne(['owner' => $user]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/play/'.$maxfield->getPath());

        self::assertResponseIsSuccessful();
    }
}
