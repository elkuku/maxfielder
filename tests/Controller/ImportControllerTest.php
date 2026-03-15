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

        $kExportData = json_encode([
            'portals' => [
                [
                    'type' => 'portal',
                    'data' => [
                        'title' => 'Portal Alpha',
                        'latitude' => 48.0,
                        'longitude' => 11.0,
                        'guid' => 'guid-alpha',
                        'image' => 'https://example.com/img.jpg',
                    ],
                ],
            ],
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

        $kExportData = json_encode([
            'portals' => [
                [
                    'type' => 'portal',
                    'data' => [
                        'title' => 'Portal Alpha',
                        'latitude' => 48.0,
                        'longitude' => 11.0,
                        'guid' => 'guid-alpha',
                        'image' => '',
                    ],
                ],
            ],
        ]);

        $crawler = $client->request(Request::METHOD_GET, '/import');
        $form = $crawler->selectButton('Import')->form();
        $client->submit($form, ['import_form[kexport]' => (string) $kExportData]);

        self::assertResponseRedirects('/');
    }
}
