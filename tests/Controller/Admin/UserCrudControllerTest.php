<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Enum\UserRole;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class UserCrudControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testNewUserPageRendersWithoutErrorsForAdmin(): void
    {
        $client = self::createClient();
        $admin = UserFactory::createOne(['role' => UserRole::ADMIN]);
        $client->loginUser($admin);

        $client->request(Request::METHOD_GET, '/admin/user/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('div.alert-danger');
    }

    public function testProfileRendersForGoogleAgentUser(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT, 'googleId' => 'google-id-123']);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/profile');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('div.alert-danger');
    }

    public function testAdminCanPromoteAgentUserToAdmin(): void
    {
        $client = self::createClient();

        $admin = UserFactory::new()->asAdmin()->create();
        $agent = UserFactory::createOne(['role' => UserRole::AGENT]);

        $client->loginUser($admin);

        $agentId = $agent->getId();
        $crawler = $client->request(Request::METHOD_GET, '/admin/user/'.$agentId.'/edit');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('div.alert-danger');

        // Use getPhpValues() to preserve the CSRF token, then override roles
        $form = $crawler->selectButton('Save changes')->form();
        $values = $form->getPhpValues();
        $values['User']['roles'] = [UserRole::ADMIN->value];

        $client->request(Request::METHOD_POST, '/admin/user/'.$agentId.'/edit', $values);

        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('div.alert-danger');
    }
}
