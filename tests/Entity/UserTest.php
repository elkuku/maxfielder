<?php

namespace App\Tests\Entity;

use App\Entity\Maxfield;
use App\Entity\User;
use App\Enum\MapBoxProfilesEnum;
use App\Enum\MapBoxStylesEnum;
use App\Enum\MapProvidersEnum;
use App\Enum\UserRole;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testToString(): void
    {
        $user = new User();
        $user->setIdentifier('agent007');

        self::assertSame('agent007', (string)$user);
    }

    public function testGetRolesDefaultIncludesRoleUser(): void
    {
        $user = new User();

        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testGetRolesAdminIncludesRoleUser(): void
    {
        $user = new User();
        $user->setRole(UserRole::ADMIN);

        $roles = $user->getRoles();

        self::assertContains('ROLE_ADMIN', $roles);
        self::assertContains('ROLE_USER', $roles);
        self::assertCount(2, $roles);
    }

    public function testGetRolesUserNoDuplicates(): void
    {
        $user = new User();
        $user->setRole(UserRole::USER);

        $roles = $user->getRoles();

        self::assertSame(['ROLE_USER'], $roles);
    }

    public function testGetParamMissingKeyReturnsEmptyString(): void
    {
        $user = new User();

        self::assertSame('', $user->getParam('nonexistent'));
    }

    public function testGetParamExistingKeyReturnsValue(): void
    {
        $user = new User();
        $user->setParams(['agentName' => 'TestAgent']);

        self::assertSame('TestAgent', $user->getParam('agentName'));
    }

    public function testGetParamFalsyValueReturnsEmptyString(): void
    {
        $user = new User();
        $user->setParams(['key' => '']);

        self::assertSame('', $user->getParam('key'));
    }

    #[RunInSeparateProcess]
    public function testGetUserParamsDefaults(): void
    {
        $user = new User();

        $settings = $user->getUserParams();

        self::assertSame('', $settings->agentName);
        self::assertSame(0.0, $settings->lat);
        self::assertSame(0.0, $settings->lon);
        self::assertSame(0, $settings->zoom);
        self::assertSame('', $settings->mapboxApiKey);
        self::assertSame(MapBoxStylesEnum::Standard, $settings->defaultStyle);
        self::assertSame(MapBoxProfilesEnum::Driving, $settings->defaultProfile);
        self::assertSame(MapProvidersEnum::leaflet, $settings->mapProvider);
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

        self::assertSame('MyAgent', $settings->agentName);
        self::assertSame(48.123, $settings->lat);
        self::assertSame(11.456, $settings->lon);
        self::assertSame(15, $settings->zoom);
        self::assertSame('pk.test123', $settings->mapboxApiKey);
        self::assertSame(MapBoxStylesEnum::Dark, $settings->defaultStyle);
        self::assertSame(MapBoxProfilesEnum::Walking, $settings->defaultProfile);
        self::assertSame(MapProvidersEnum::mapbox, $settings->mapProvider);
    }

    public function testAddMaxfield(): void
    {
        $user = new User();
        $maxfield = new Maxfield();

        $user->addMaxfield($maxfield);

        self::assertCount(1, $user->getMaxfields());
        self::assertSame($user, $maxfield->getOwner());
    }

    public function testAddMaxfieldNoDuplicates(): void
    {
        $user = new User();
        $maxfield = new Maxfield();

        $user->addMaxfield($maxfield);
        $user->addMaxfield($maxfield);

        self::assertCount(1, $user->getMaxfields());
    }

    public function testRemoveMaxfield(): void
    {
        $user = new User();
        $maxfield = new Maxfield();

        $user->addMaxfield($maxfield);
        $user->removeMaxfield($maxfield);

        self::assertCount(0, $user->getMaxfields());
        self::assertNull($maxfield->getOwner());
    }

    public function testRemoveMaxfieldDoesNotClearOwnerIfChanged(): void
    {
        $user1 = new User();
        $user2 = new User();
        $maxfield = new Maxfield();

        $user1->addMaxfield($maxfield);
        $maxfield->setOwner($user2);
        $user1->removeMaxfield($maxfield);

        self::assertSame($user2, $maxfield->getOwner());
    }

    public function testToggleFavouriteAdd(): void
    {
        $user = new User();
        $maxfield = new Maxfield();

        $result = $user->toggleFavourite($maxfield);

        self::assertTrue($result);
        self::assertCount(1, $user->getFavourites());
    }

    public function testToggleFavouriteRemove(): void
    {
        $user = new User();
        $maxfield = new Maxfield();

        $user->addFavourite($maxfield);
        $result = $user->toggleFavourite($maxfield);

        self::assertFalse($result);
        self::assertCount(0, $user->getFavourites());
    }

    public function testSerializeUnserializeRoundtrip(): void
    {
        $user = new User();
        $user->setIdentifier('testuser');

        $reflection = new \ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($user, 42);

        $data = $user->__serialize();

        $newUser = new User();
        $newUser->__unserialize($data);

        self::assertSame(42, $newUser->getId());
        self::assertSame('testuser', $newUser->getIdentifier());
    }

    public function testUnserializeWithNulls(): void
    {
        $user = new User();
        $user->__unserialize(['id' => null, 'identifier' => null]);

        self::assertNull($user->getId());
        self::assertSame('', $user->getIdentifier());
    }

    public function testGetSetGoogleId(): void
    {
        $user = new User();

        self::assertNull($user->getGoogleId());

        $user->setGoogleId('google123');
        self::assertSame('google123', $user->getGoogleId());
    }

    public function testGetSetGitHubId(): void
    {
        $user = new User();

        self::assertNull($user->getGitHubId());

        $user->setGitHubId(456);
        self::assertSame(456, $user->getGitHubId());
    }

    public function testUserIdentifier(): void
    {
        $user = new User();
        $user->setIdentifier('myident');

        self::assertSame('myident', $user->getUserIdentifier());
    }

    public function testGetPasswordReturnsNull(): void
    {
        $user = new User();

        self::assertNull($user->getPassword());
    }
}
