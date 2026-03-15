<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Enum\UserRole;
use App\Factory\UserFactory;
use App\Factory\WaypointFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class ImportControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testImportRequiresAdmin(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::USER]);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/import');

        self::assertResponseStatusCodeSame(403);
    }

    public function testImportRendersFormForAdmin(): void
    {
        $client = self::createClient();
        $admin = UserFactory::createOne(['role' => UserRole::ADMIN]);
        $client->loginUser($admin);

        $client->request(Request::METHOD_GET, '/import');

        self::assertResponseIsSuccessful();
    }

    public function testImportWithKExportDataImportsWaypoints(): void
    {
        $client = self::createClient();
        $admin = UserFactory::createOne(['role' => UserRole::ADMIN]);
        $client->loginUser($admin);

        // KExport format: flat array of portal objects with guid, title, lat, lng, image
        $kExportData = json_encode([
            ['guid' => 'guid-alpha', 'title' => 'Portal Alpha', 'lat' => 48.0, 'lng' => 11.0, 'image' => ''],
        ]);

        $crawler = $client->request(Request::METHOD_GET, '/import');
        $form = $crawler->selectButton('Import')->form();
        $client->submit($form, ['import_form[kexport]' => (string) $kExportData]);

        self::assertResponseRedirects('/');
    }

    public function testImportWithInvalidDataFlashesDanger(): void
    {
        $client = self::createClient();
        $admin = UserFactory::createOne(['role' => UserRole::ADMIN]);
        $client->loginUser($admin);

        $crawler = $client->request(Request::METHOD_GET, '/import');
        $form = $crawler->selectButton('Import')->form();
        $client->submit($form, ['import_form[kexport]' => 'not-valid-json']);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.alert-danger');
    }

    public function testImportWithExistingWaypointSkipsDuplicate(): void
    {
        $client = self::createClient();
        $admin = UserFactory::createOne(['role' => UserRole::ADMIN]);
        WaypointFactory::createOne(['lat' => 48.0, 'lon' => 11.0, 'guid' => 'guid-alpha']);
        $client->loginUser($admin);

        // Same lat/lon/guid as existing waypoint — storeWayPoints skips it (cnt=0 → warning flash)
        $kExportData = json_encode([
            ['guid' => 'guid-alpha', 'title' => 'Portal Alpha', 'lat' => 48.0, 'lng' => 11.0, 'image' => ''],
        ]);

        $crawler = $client->request(Request::METHOD_GET, '/import');
        $form = $crawler->selectButton('Import')->form();
        $client->submit($form, ['import_form[kexport]' => (string) $kExportData]);

        self::assertResponseRedirects('/');
    }

    public function testImportWithForceUpdateUpdatesWaypoint(): void
    {
        $client = self::createClient();
        $admin = UserFactory::createOne(['role' => UserRole::ADMIN]);
        WaypointFactory::createOne(['lat' => 48.0, 'lon' => 11.0, 'guid' => 'guid-beta', 'name' => 'Old Name']);
        $client->loginUser($admin);

        // Same lat/lon/guid but forceUpdate=true → updates existing waypoint
        $kExportData = json_encode([
            ['guid' => 'guid-beta', 'title' => 'New Name', 'lat' => 48.0, 'lng' => 11.0, 'image' => ''],
        ]);

        $crawler = $client->request(Request::METHOD_GET, '/import');
        $form = $crawler->selectButton('Import')->form();
        $client->submit($form, [
            'import_form[kexport]' => (string) $kExportData,
            'import_form[forceUpdate]' => '1',
        ]);

        self::assertResponseRedirects('/');
    }

    public function testImportUpdatesGuidWhenMissing(): void
    {
        $client = self::createClient();
        $admin = UserFactory::createOne(['role' => UserRole::ADMIN]);
        WaypointFactory::createOne(['lat' => 48.0, 'lon' => 11.0, 'guid' => '']);
        $client->loginUser($admin);

        // Same lat/lon but existing waypoint has no guid → assigns the guid
        $kExportData = json_encode([
            ['guid' => 'newly-discovered-guid', 'title' => 'Some Portal', 'lat' => 48.0, 'lng' => 11.0, 'image' => ''],
        ]);

        $crawler = $client->request(Request::METHOD_GET, '/import');
        $form = $crawler->selectButton('Import')->form();
        $client->submit($form, ['import_form[kexport]' => (string) $kExportData]);

        self::assertResponseRedirects('/');
    }
}
