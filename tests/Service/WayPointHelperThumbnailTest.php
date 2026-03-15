<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\WayPointHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class WayPointHelperThumbnailTest extends TestCase
{
    private string $tempDir;

    private WayPointHelper $helper;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/maxfielder_thumb_test_'.uniqid();
        mkdir($this->tempDir.'/public/wp_images/thumbs', 0777, true);
        $this->helper = new WayPointHelper($this->tempDir, 'https://intel.ingress.com/intel');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tempDir);
    }

    private function createTestJpeg(string $path): void
    {
        $img = imagecreatetruecolor(100, 100);
        $this->assertNotFalse($img);
        $color = imagecolorallocate($img, 100, 150, 200);
        $this->assertNotFalse($color);
        imagefill($img, 0, 0, $color);
        imagejpeg($img, $path);
        imagedestroy($img);
    }

    public function testGetThumbnailPathCreatesThumb(): void
    {
        $wpId = 'test-portal-guid';
        $imagePath = $this->tempDir.'/public/wp_images/'.$wpId.'.jpg';
        $this->createTestJpeg($imagePath);

        $thumbPath = $this->helper->getThumbnailPath($wpId, 'https://example.com/image.jpg');

        $this->assertFileExists($thumbPath);
        $this->assertStringContainsString('thumbs', $thumbPath);
    }

    public function testGetThumbnailPathReturnsCachedThumb(): void
    {
        $wpId = 'cached-guid';
        $imagePath = $this->tempDir.'/public/wp_images/'.$wpId.'.jpg';
        $thumbPath = $this->tempDir.'/public/wp_images/thumbs/'.$wpId.'.jpg';

        $this->createTestJpeg($imagePath);

        // Pre-create the thumbnail
        $this->createTestJpeg($thumbPath);

        $result = $this->helper->getThumbnailPath($wpId, 'https://example.com/image.jpg');

        $this->assertSame($thumbPath, $result);
    }

    public function testGetThumbnailDimensionsAreReduced(): void
    {
        $wpId = 'size-test-guid';
        $imagePath = $this->tempDir.'/public/wp_images/'.$wpId.'.jpg';
        $this->createTestJpeg($imagePath); // 100x100

        $thumbPath = $this->helper->getThumbnailPath($wpId, 'https://example.com/image.jpg');

        $size = getimagesize($thumbPath);
        $this->assertNotFalse($size);
        $this->assertSame(60, $size[0]); // default desiredWidth = 60
        $this->assertSame(60, $size[1]); // square source → square thumb
    }

    public function testCheckImageSkipsWhenImageAlreadyExists(): void
    {
        $wpId = 'existing-guid';
        $imagePath = $this->tempDir.'/public/wp_images/'.$wpId.'.jpg';
        $this->createTestJpeg($imagePath);

        $originalMtime = filemtime($imagePath);

        // checkImage with forceUpdate=false should not re-download
        $this->helper->checkImage($wpId, 'https://example.com/image.jpg', false);

        $this->assertSame($originalMtime, filemtime($imagePath));
    }
}
