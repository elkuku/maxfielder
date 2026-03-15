<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use UnexpectedValueException;
use App\Enum\UserRole;
use App\Factory\UserFactory;
use App\Factory\WaypointFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class WaypointsControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testMapEndpointRequiresAgentRole(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::USER]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/waypoints_map');

        self::assertResponseStatusCodeSame(403);
    }

    public function testMapEndpointReturnsJsonForAgent(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        WaypointFactory::createOne(['name' => 'Portal A', 'lat' => 48.0, 'lon' => 11.0]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/waypoints_map');

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
        /** @var array<array<string, mixed>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Portal A', $data[0]['name']);
    }

    public function testMapEndpointWithBoundsFilters(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        WaypointFactory::createOne(['name' => 'Inside', 'lat' => 48.0, 'lon' => 11.0]);
        WaypointFactory::createOne(['name' => 'Outside', 'lat' => 60.0, 'lon' => 20.0]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/waypoints_map', ['bounds' => '50.0,13.0,47.0,10.0']);

        self::assertResponseIsSuccessful();
        /** @var array<array<string, mixed>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertSame('Inside', $data[0]['name']);
    }

    public function testMapEndpointWithInvalidBoundsThrows(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        $client->loginUser($user);
        $client->catchExceptions(false);

        $this->expectException(UnexpectedValueException::class);
        $client->request(Request::METHOD_GET, '/waypoints_map', ['bounds' => '50.0,13.0']);
    }

    public function testInfoRequiresAdmin(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::USER]);
        $wp = WaypointFactory::createOne();
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/waypoints_info/'.$wp->getId());

        self::assertResponseStatusCodeSame(403);
    }

    public function testInfoRendersForAdmin(): void
    {
        $client = self::createClient();
        $admin = UserFactory::createOne(['role' => UserRole::ADMIN]);
        $wp = WaypointFactory::createOne(['name' => 'Test Portal']);
        $client->loginUser($admin);

        $client->request(Request::METHOD_GET, '/waypoints_info/'.$wp->getId());

        self::assertResponseIsSuccessful();
    }

    public function testEditRequiresAdmin(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::USER]);
        $wp = WaypointFactory::createOne();
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/waypoint/'.$wp->getId());

        self::assertResponseStatusCodeSame(403);
    }

    public function testEditRendersForAdmin(): void
    {
        $client = self::createClient();
        $admin = UserFactory::createOne(['role' => UserRole::ADMIN]);
        $wp = WaypointFactory::createOne(['name' => 'My Portal']);
        $client->loginUser($admin);

        $client->request(Request::METHOD_GET, '/waypoint/'.$wp->getId());

        self::assertResponseIsSuccessful();
    }
}
