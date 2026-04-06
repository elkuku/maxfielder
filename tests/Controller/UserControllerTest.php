<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Enum\UserRole;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class UserControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testProfileRendersForAuthenticatedUser(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::USER]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/profile');

        self::assertResponseIsSuccessful();
    }

    public function testProfileRedirectsForAnonymousUser(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, '/profile');

        self::assertResponseRedirects('/login');
    }

    public function testProfileWithExistingParamsRendersValues(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne([
            'role' => UserRole::AGENT,
            'params' => ['agentName' => 'AgentX', 'lat' => '48.0', 'lon' => '11.0', 'zoom' => '12'],
        ]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/profile');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
    }

    public function testProfileSubmitSavesParamsAndRedirects(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $client->loginUser($user);

        $crawler = $client->request(Request::METHOD_GET, '/profile');
        $token = $crawler->filter('[name="profile_form[_token]"]')->attr('value');

        $client->request(Request::METHOD_POST, '/profile', [
            'profile_form' => [
                '_token' => $token,
                'agentName' => 'TestAgent',
                'lat' => '48',
                'lon' => '11',
                'zoom' => '10',
                'mapboxApiKey' => '',
                'defaultStyle' => 'mapbox/standard',
                'defaultProfile' => 'mapbox/driving',
                'mapProvider' => 'leaflet',
                'maxfieldEngine' => 'php',
                'dockerContainer' => '',
            ],
        ]);

        self::assertResponseRedirects('/');
    }
}
