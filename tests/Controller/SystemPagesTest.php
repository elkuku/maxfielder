<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * The About and deploy log pages of the KuKu system tools bundle.
 */
final class SystemPagesTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testAdminSeesThePages(): void
    {
        $client = self::createClient();
        $client->loginUser(UserFactory::new()->asAdmin()->create());

        $client->request(Request::METHOD_GET, '/system/about');
        self::assertResponseIsSuccessful();
        self::assertRouteSame('kuku_sf_system_about');

        $client->request(Request::METHOD_GET, '/system/logview');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('p', 'var/log/deploy.log');
    }

    public function testThePagesNeedTheAdminRole(): void
    {
        $client = self::createClient();
        $client->loginUser(UserFactory::createOne());

        $client->request(Request::METHOD_GET, '/system/about');
        self::assertResponseStatusCodeSame(403);

        $client->request(Request::METHOD_GET, '/system/logview');
        self::assertResponseStatusCodeSame(403);
    }
}
