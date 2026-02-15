<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use ReflectionClass;
use App\Entity\Maxfield;
use App\Entity\User;
use App\Enum\MapBoxProfilesEnum;
use App\Enum\MapBoxStylesEnum;
use App\Enum\MapProvidersEnum;
use App\Enum\UserRole;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testToString(): void
    {
        $user = new User();
        $user->setIdentifier('agent007');

        $this->assertSame('agent007', (string)$user);
    }

    public function testGetRolesDefaultIncludesRoleUser(): void
    {
        $user = new User();

        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testGetRolesAdminIncludesRoleUser(): void
    {
        $user = new User();
        $user->setRole(UserRole::ADMIN);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertCount(2, $roles);
    }

    public function testGetRolesUserNoDuplicates(): void
    {
        $user = new User();
        $user->setRole(UserRole::USER);

        $roles = $user->getRoles();

        $this->assertSame(['ROLE_USER'], $roles);
    }

    public function testGetParamMissingKeyReturnsEmptyString(): void
    {
        $user = new User();

        $this->assertSame('', $user->getParam('nonexistent'));
    }

    public function testGetParamExistingKeyReturnsValue(): void
    {
        $user = new User();
        $user->setParams(['agentName' => 'TestAgent']);

        $this->assertSame('TestAgent', $user->getParam('agentName'));
    }

    public function testGetParamFalsyValueReturnsEmptyString(): void
    {
        $user = new User();
        $user->setParams(['key' => '']);

        $this->assertSame('', $user->getParam('key'));
    }

    #[RunInSeparateProcess]
    public function testGetUserParamsDefaults(): void
    {
        $user = new User();

        $settings = $user->getUserParams();

        $this->assertSame('', $settings->agentName);
        $this->assertEqualsWithDelta(0.0, $settings->lat, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $settings->lon, PHP_FLOAT_EPSILON);
        $this->assertSame(0, $settings->zoom);
        $this->assertSame('', $settings->mapboxApiKey);
        $this->assertSame(MapBoxStylesEnum::Standard, $settings->defaultStyle);
        $this->assertSame(MapBoxProfilesEnum::Driving, $settings->defaultProfile);
        $this->assertSame(MapProvidersEnum::leaflet, $settings->mapProvider);
    }

    #[RunInSeparateProcess]
    public function testGetUserParamsWithValues(): void
    {
        $user = new User();
        $user->setParams([
            'agentName' => 'MyAgent',
            'lat' => '48.123',
            'lon' => '11.456',
            'zoom' => '15',
            'mapboxApiKey' => 'pk.test123',
            'defaultStyle' => 'mapbox/dark-v11',
            'defaultProfile' => 'mapbox/walking',
            'mapProvider' => 'mapbox',
        ]);

        $settings = $user->getUserParams();

        $this->assertSame('MyAgent', $settings->agentName);
        $this->assertEqualsWithDelta(48.123, $settings->lat, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(11.456, $settings->lon, PHP_FLOAT_EPSILON);
        $this->assertSame(15, $settings->zoom);
        $this->assertSame('pk.test123', $settings->mapboxApiKey);
        $this->assertSame(MapBoxStylesEnum::Dark, $settings->defaultStyle);
        $this->assertSame(MapBoxProfilesEnum::Walking, $settings->defaultProfile);
        $this->assertSame(MapProvidersEnum::mapbox, $settings->mapProvider);
    }

    public function testAddMaxfield(): void
    {
        $user = new User();
        $maxfield = new Maxfield();

        $user->addMaxfield($maxfield);

        $this->assertCount(1, $user->getMaxfields());
        $this->assertSame($user, $maxfield->getOwner());
    }

    public function testAddMaxfieldNoDuplicates(): void
    {
        $user = new User();
        $maxfield = new Maxfield();

        $user->addMaxfield($maxfield);
        $user->addMaxfield($maxfield);

        $this->assertCount(1, $user->getMaxfields());
    }

    public function testRemoveMaxfield(): void
    {
        $user = new User();
        $maxfield = new Maxfield();

        $user->addMaxfield($maxfield);
        $user->removeMaxfield($maxfield);

        $this->assertCount(0, $user->getMaxfields());
        $this->assertNotInstanceOf(User::class, $maxfield->getOwner());
    }

    public function testRemoveMaxfieldDoesNotClearOwnerIfChanged(): void
    {
        $user1 = new User();
        $user2 = new User();
        $maxfield = new Maxfield();

        $user1->addMaxfield($maxfield);
        $maxfield->setOwner($user2);
        $user1->removeMaxfield($maxfield);

        $this->assertSame($user2, $maxfield->getOwner());
    }

    public function testToggleFavouriteAdd(): void
    {
        $user = new User();
        $maxfield = new Maxfield();

        $result = $user->toggleFavourite($maxfield);

        $this->assertTrue($result);
        $this->assertCount(1, $user->getFavourites());
    }

    public function testToggleFavouriteRemove(): void
    {
        $user = new User();
        $maxfield = new Maxfield();

        $user->addFavourite($maxfield);
        $result = $user->toggleFavourite($maxfield);

        $this->assertFalse($result);
        $this->assertCount(0, $user->getFavourites());
    }

    public function testSerializeUnserializeRoundtrip(): void
    {
        $user = new User();
        $user->setIdentifier('testuser');

        $reflection = new ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($user, 42);

        $data = $user->__serialize();

        $newUser = new User();
        $newUser->__unserialize($data);

        $this->assertSame(42, $newUser->getId());
        $this->assertSame('testuser', $newUser->getIdentifier());
    }

    public function testUnserializeWithNulls(): void
    {
        $user = new User();
        $user->__unserialize(['id' => null, 'identifier' => null]);

        $this->assertNull($user->getId());
        $this->assertSame('', $user->getIdentifier());
    }

    public function testGetSetGoogleId(): void
    {
        $user = new User();

        $this->assertNull($user->getGoogleId());

        $user->setGoogleId('google123');
        $this->assertSame('google123', $user->getGoogleId());
    }

    public function testGetSetGitHubId(): void
    {
        $user = new User();

        $this->assertNull($user->getGitHubId());

        $user->setGitHubId(456);
        $this->assertSame(456, $user->getGitHubId());
    }

    public function testUserIdentifier(): void
    {
        $user = new User();
        $user->setIdentifier('myident');

        $this->assertSame('myident', $user->getUserIdentifier());
    }

    public function testGetPasswordReturnsNull(): void
    {
        $user = new User();

        $this->assertNull($user->getPassword());
    }
}
