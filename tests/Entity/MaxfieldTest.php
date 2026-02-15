<?php

namespace App\Tests\Entity;

use App\Entity\Maxfield;
use App\Entity\User;
use App\Type\AgentKeyInfo;
use PHPUnit\Framework\TestCase;

class MaxfieldTest extends TestCase
{
    public function testGetSetName(): void
    {
        $maxfield = new Maxfield();

        self::assertNull($maxfield->getName());

        $result = $maxfield->setName('Test Field');

        self::assertSame('Test Field', $maxfield->getName());
        self::assertSame($maxfield, $result);
    }

    public function testGetSetPath(): void
    {
        $maxfield = new Maxfield();

        self::assertNull($maxfield->getPath());

        $maxfield->setPath('some/path');
        self::assertSame('some/path', $maxfield->getPath());
    }

    public function testGetSetOwner(): void
    {
        $maxfield = new Maxfield();
        $user = new User();

        self::assertNull($maxfield->getOwner());

        $maxfield->setOwner($user);
        self::assertSame($user, $maxfield->getOwner());

        $maxfield->setOwner(null);
        self::assertNull($maxfield->getOwner());
    }

    public function testGetSetJsonData(): void
    {
        $maxfield = new Maxfield();

        self::assertNull($maxfield->getJsonData());

        $data = ['waypoints' => []];
        $maxfield->setJsonData($data);
        self::assertSame($data, $maxfield->getJsonData());
    }

    public function testGetSetUserData(): void
    {
        $maxfield = new Maxfield();

        self::assertNull($maxfield->getUserData());

        $key = new AgentKeyInfo();
        $key->guid = 'g1';
        $key->name = 'Portal';
        $key->link = 'link';
        $key->count = 1;
        $key->capsules = 'cap';

        $data = [1 => ['keys' => [$key]]];
        $maxfield->setUserData($data);
        self::assertSame($data, $maxfield->getUserData());
    }

    public function testSetUserKeysWithUserInitializesWhenNull(): void
    {
        $maxfield = new Maxfield();
        $key = $this->createAgentKeyInfo('Portal A', 'guid1', 3);

        $result = $maxfield->setUserKeysWithUser([$key], 1);

        $userData = $maxfield->getUserData();
        self::assertNotNull($userData);
        self::assertSame([$key], $userData[1]['keys'] ?? null);
        self::assertSame($maxfield, $result);
    }

    public function testSetUserKeysWithUserMergesWhenExisting(): void
    {
        $maxfield = new Maxfield();
        $key1 = $this->createAgentKeyInfo('Portal A', 'guid1', 1);

        $initialData = [1 => ['keys' => [$key1]]];
        $maxfield->setUserData($initialData);

        $key2 = $this->createAgentKeyInfo('Portal B', 'guid2', 5);
        $maxfield->setUserKeysWithUser([$key2], 1);

        $userData = $maxfield->getUserData();
        self::assertNotNull($userData);
        self::assertSame([$key2], $userData[1]['keys'] ?? null);
    }

    public function testSetUserKeysWithUserDifferentUsers(): void
    {
        $maxfield = new Maxfield();
        $key1 = $this->createAgentKeyInfo('Portal A', 'guid1', 1);
        $key2 = $this->createAgentKeyInfo('Portal B', 'guid2', 2);

        $maxfield->setUserKeysWithUser([$key1], 1);
        $maxfield->setUserKeysWithUser([$key2], 2);

        $userData = $maxfield->getUserData();
        self::assertNotNull($userData);
        self::assertSame([$key1], $userData[1]['keys'] ?? null);
        self::assertSame([$key2], $userData[2]['keys'] ?? null);
    }

    public function testSetCurrentPointWithUserInitializesWhenNull(): void
    {
        $maxfield = new Maxfield();

        $result = $maxfield->setCurrentPointWithUser('PointA', 1);

        $userData = $maxfield->getUserData();
        self::assertNotNull($userData);
        self::assertSame('PointA', $userData[1]['current_point'] ?? null);
        self::assertSame($maxfield, $result);
    }

    public function testSetCurrentPointWithUserMergesWhenExisting(): void
    {
        $maxfield = new Maxfield();
        $key = $this->createAgentKeyInfo('Portal', 'guid', 1);
        $initialData = [1 => ['keys' => [$key]]];
        $maxfield->setUserData($initialData);

        $maxfield->setCurrentPointWithUser('PointB', 1);

        $userData = $maxfield->getUserData();
        self::assertNotNull($userData);
        self::assertSame('PointB', $userData[1]['current_point'] ?? null);
    }

    public function testSetFarmDoneWithUserInitializesWhenNull(): void
    {
        $maxfield = new Maxfield();

        $result = $maxfield->setFarmDoneWithUser([1, 2, 3], 1);

        $userData = $maxfield->getUserData();
        self::assertNotNull($userData);
        self::assertSame([1, 2, 3], $userData[1]['farm_done'] ?? null);
        self::assertSame($maxfield, $result);
    }

    public function testSetFarmDoneWithUserMergesWhenExisting(): void
    {
        $maxfield = new Maxfield();
        $key = $this->createAgentKeyInfo('Portal', 'guid', 1);
        $initialData = [1 => ['keys' => [$key]]];
        $maxfield->setUserData($initialData);

        $maxfield->setFarmDoneWithUser([4, 5], 1);

        $userData = $maxfield->getUserData();
        self::assertNotNull($userData);
        self::assertSame([4, 5], $userData[1]['farm_done'] ?? null);
    }

    public function testIdIsNullByDefault(): void
    {
        $maxfield = new Maxfield();

        self::assertNull($maxfield->getId());
    }

    private function createAgentKeyInfo(string $name, string $guid, int $count): AgentKeyInfo
    {
        $key = new AgentKeyInfo();
        $key->name = $name;
        $key->guid = $guid;
        $key->link = 'https://example.com';
        $key->count = $count;
        $key->capsules = '';

        return $key;
    }
}
