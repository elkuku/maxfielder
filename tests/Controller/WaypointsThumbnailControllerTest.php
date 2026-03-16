<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Enum\UserRole;
use App\Factory\UserFactory;
use App\Factory\WaypointFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Tests for WaypointsController thumbnail route that requires an image on disk.
 * Pre-creates a cached thumbnail so no network call is needed.
 */
final class WaypointsThumbnailControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private const string TEST_GUID = 'test-thumbnail-ctrl-guid';

    private string $thumbsDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->thumbsDir = dirname(__DIR__, 2).'/public/wp_images/thumbs';
        (new Filesystem())->mkdir($this->thumbsDir);

        // Create a minimal 60×60 JPEG as a pre-cached thumbnail
        $img = imagecreatetruecolor(60, 60);
        $this->assertNotFalse($img);
        imagejpeg($img, $this->thumbsDir.'/'.self::TEST_GUID.'.jpg');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->thumbsDir.'/'.self::TEST_GUID.'.jpg');
        parent::tearDown();
    }

    public function testGetImageThumbnailReturnsBinaryResponse(): void
    {
        $client = self::createClient();
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        WaypointFactory::createOne(['guid' => self::TEST_GUID, 'image' => 'https://example.com/img.jpg']);
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/waypoint_thumb/'.self::TEST_GUID);

        self::assertResponseIsSuccessful();
    }
}
