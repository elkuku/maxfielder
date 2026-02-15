<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Maxfield;
use App\Entity\User;
use App\Type\AgentKeyInfo;
use PHPUnit\Framework\TestCase;

final class MaxfieldTest extends TestCase
{
    public function testGetSetName(): void
    {
        $maxfield = new Maxfield();

        $this->assertNull($maxfield->getName());

        $result = $maxfield->setName('Test Field');

        $this->assertSame('Test Field', $maxfield->getName());
        $this->assertSame($maxfield, $result);
    }

    public function testGetSetPath(): void
    {
        $maxfield = new Maxfield();

        $this->assertNull($maxfield->getPath());

        $maxfield->setPath('some/path');
        $this->assertSame('some/path', $maxfield->getPath());
    }

    public function testGetSetOwner(): void
    {
        $maxfield = new Maxfield();
        $user = new User();

        $this->assertNotInstanceOf(User::class, $maxfield->getOwner());

        $maxfield->setOwner($user);
        $this->assertSame($user, $maxfield->getOwner());

        $maxfield->setOwner(null);
        $this->assertNull($maxfield->getOwner());
    }

    public function testGetSetJsonData(): void
    {
        $maxfield = new Maxfield();

        $this->assertNull($maxfield->getJsonData());

        $data = ['waypoints' => []];
        $maxfield->setJsonData($data);
        $this->assertSame($data, $maxfield->getJsonData());
    }

    public function testGetSetUserData(): void
    {
        $maxfield = new Maxfield();

        $this->assertNull($maxfield->getUserData());

        $key = new AgentKeyInfo();
        $key->guid = 'g1';
        $key->name = 'Portal';
        $key->link = 'link';
        $key->count = 1;
        $key->capsules = 'cap';

        $data = [1 => ['keys' => [$key]]];
        $maxfield->setUserData($data);
        $this->assertSame($data, $maxfield->getUserData());
    }

    public function testSetUserKeysWithUserInitializesWhenNull(): void
    {
        $maxfield = new Maxfield();
        $key = $this->createAgentKeyInfo('Portal A', 'guid1', 3);

        $result = $maxfield->setUserKeysWithUser([$key], 1);

        $userData = $maxfield->getUserData();
        $this->assertNotNull($userData);
        $this->assertSame([$key], $userData[1]['keys'] ?? null);
        $this->assertSame($maxfield, $result);
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
        $this->assertNotNull($userData);
        $this->assertSame([$key2], $userData[1]['keys'] ?? null);
    }

    public function testSetUserKeysWithUserDifferentUsers(): void
    {
        $maxfield = new Maxfield();
        $key1 = $this->createAgentKeyInfo('Portal A', 'guid1', 1);
        $key2 = $this->createAgentKeyInfo('Portal B', 'guid2', 2);

        $maxfield->setUserKeysWithUser([$key1], 1);
        $maxfield->setUserKeysWithUser([$key2], 2);

        $userData = $maxfield->getUserData();
        $this->assertNotNull($userData);
        $this->assertSame([$key1], $userData[1]['keys'] ?? null);
        $this->assertSame([$key2], $userData[2]['keys'] ?? null);
    }

    public function testSetCurrentPointWithUserInitializesWhenNull(): void
    {
        $maxfield = new Maxfield();

        $result = $maxfield->setCurrentPointWithUser('PointA', 1);

        $userData = $maxfield->getUserData();
        $this->assertNotNull($userData);
        $this->assertSame('PointA', $userData[1]['current_point'] ?? null);
        $this->assertSame($maxfield, $result);
    }

    public function testSetCurrentPointWithUserMergesWhenExisting(): void
    {
        $maxfield = new Maxfield();
        $key = $this->createAgentKeyInfo('Portal', 'guid', 1);
        $initialData = [1 => ['keys' => [$key]]];
        $maxfield->setUserData($initialData);

        $maxfield->setCurrentPointWithUser('PointB', 1);

        $userData = $maxfield->getUserData();
        $this->assertNotNull($userData);
        $this->assertSame('PointB', $userData[1]['current_point'] ?? null);
    }

    public function testSetFarmDoneWithUserInitializesWhenNull(): void
    {
        $maxfield = new Maxfield();

        $result = $maxfield->setFarmDoneWithUser([1, 2, 3], 1);

        $userData = $maxfield->getUserData();
        $this->assertNotNull($userData);
        $this->assertSame([1, 2, 3], $userData[1]['farm_done'] ?? null);
        $this->assertSame($maxfield, $result);
    }

    public function testSetFarmDoneWithUserMergesWhenExisting(): void
    {
        $maxfield = new Maxfield();
        $key = $this->createAgentKeyInfo('Portal', 'guid', 1);
        $initialData = [1 => ['keys' => [$key]]];
        $maxfield->setUserData($initialData);

        $maxfield->setFarmDoneWithUser([4, 5], 1);

        $userData = $maxfield->getUserData();
        $this->assertNotNull($userData);
        $this->assertSame([4, 5], $userData[1]['farm_done'] ?? null);
    }

    public function testIdIsNullByDefault(): void
    {
        $maxfield = new Maxfield();

        $this->assertNull($maxfield->getId());
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
