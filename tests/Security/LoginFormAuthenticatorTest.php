<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class LoginFormAuthenticatorTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testLoginPageRendersSuccessfully(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/login');

        self::assertResponseIsSuccessful();
    }

    public function testSuccessfulLoginRedirectsToDefault(): void
    {
        $client = self::createClient();
        UserFactory::createOne(['identifier' => 'testagent']);

        $client->request(Request::METHOD_POST, '/login', [
            'identifier' => 'testagent',
        ]);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testLoginWithUnknownUserFails(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_POST, '/login', [
            'identifier' => 'nobody',
        ]);

        // Redirects back to login on failure
        self::assertResponseRedirects('/login');
    }

    public function testOnAuthenticationSuccessRedirectsToTargetPath(): void
    {
        $client = self::createClient();
        UserFactory::createOne(['identifier' => 'agent42']);

        // Visit a protected page first to set the target path
        $client->request(Request::METHOD_GET, '/maxfield/list');
        self::assertResponseRedirects();

        $client->request(Request::METHOD_POST, '/login', [
            'identifier' => 'agent42',
        ]);

        // Should redirect to the originally requested page
        self::assertResponseRedirects('/maxfield/list');
    }
}
