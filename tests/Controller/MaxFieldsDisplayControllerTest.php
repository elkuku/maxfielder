<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Enum\UserRole;
use App\Factory\MaxfieldFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;


/**
 * Tests for MaxFieldsController routes that require maxfield files on disk.
 * Creates minimal fixture files in public/maxfields/ and cleans up after each test.
 */
final class MaxFieldsDisplayControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private const string TEST_PATH = 'test-fixture-maxfield';

    private string $fixtureDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtureDir = dirname(__DIR__, 2).'/public/maxfields/'.self::TEST_PATH;
        $this->createFixtureFiles();
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->fixtureDir);
        parent::tearDown();
    }

    private function createFixtureFiles(): void
    {
        $fs = new Filesystem();
        $fs->mkdir($this->fixtureDir);

        $fs->dumpFile($this->fixtureDir.'/portals_id_map.csv', "0,1,test-guid-001,\"Portal Alpha\"\n");
        $fs->dumpFile($this->fixtureDir.'/portals.txt', "Portal Alpha; https://intel.ingress.com/intel?pll=48.0,11.0\n");
        $fs->dumpFile($this->fixtureDir.'/key_preparation.csv', "KeysNeeded, KeysHave, KeysRemaining, PortalNum, PortalName\n0, 0, 0, 0, Portal Alpha\n");
        $fs->dumpFile($this->fixtureDir.'/agent_assignments.csv', "LinkNum, Agent, OriginNum, OriginName, DestinationNum, DestinationName\n");
        $fs->dumpFile($this->fixtureDir.'/agent_key_preparation.csv', "Agent, PortalNum, PortalName, KeysNeeded\n");
    }

    public function testDisplayRendersForAgent(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        MaxfieldFactory::createOne(['owner' => $user, 'path' => self::TEST_PATH]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/show/'.self::TEST_PATH);

        self::assertResponseIsSuccessful();
    }

    public function testPlayRendersForAgent(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        MaxfieldFactory::createOne(['owner' => $user, 'path' => self::TEST_PATH]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/play/'.self::TEST_PATH);

        self::assertResponseIsSuccessful();
    }

    public function testGetDataReturnsJson(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        MaxfieldFactory::createOne(['owner' => $user, 'path' => self::TEST_PATH]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/maxfield/get-data/'.self::TEST_PATH);

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('jsonData', $data);
        $this->assertArrayHasKey('waypointIdMap', $data);
    }
}
