<?php

declare(strict_types=1);

namespace App\Tests\Service;

use Symfony\Component\Filesystem\Filesystem;
use App\Service\WayPointHelper;
use PHPUnit\Framework\TestCase;

final class WayPointHelperTest extends TestCase
{
    private WayPointHelper $helper;

    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/maxfielder_wp_test_'.uniqid();
        mkdir($this->tempDir.'/public/wp_images', 0777, true);
        $this->helper = new WayPointHelper($this->tempDir, 'https://intel.ingress.com/intel');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tempDir);
    }

    public function testCleanNameTrimsWhitespace(): void
    {
        $this->assertSame('Hello', $this->helper->cleanName('  Hello  '));
    }

    public function testCleanNameRemovesPunctuation(): void
    {
        $this->assertSame('Hello World', $this->helper->cleanName('Hello. World'));
        $this->assertSame('Hello World', $this->helper->cleanName('Hello, World'));
        $this->assertSame('Hello World', $this->helper->cleanName('Hello; World'));
        $this->assertSame('Hello World', $this->helper->cleanName('Hello: World'));
        $this->assertSame('Hello World', $this->helper->cleanName('Hello" World'));
        $this->assertSame('Hello World', $this->helper->cleanName("Hello' World"));
        $this->assertSame('Hello World', $this->helper->cleanName('Hello\\ World'));
    }

    public function testCleanNameReplacesAccents(): void
    {
        $this->assertSame('cafe', $this->helper->cleanName('café'));
        $this->assertSame('ninio', $this->helper->cleanName('niño'));
        $this->assertSame('ueber', $this->helper->cleanName('über'));
        $this->assertSame('aepfel', $this->helper->cleanName('äpfel'));
        $this->assertSame('oeffnung', $this->helper->cleanName('öffnung'));
        $this->assertSame('Unico', $this->helper->cleanName('Único'));
        $this->assertSame('Otonio', $this->helper->cleanName('Otoño'));
    }

    public function testCleanNameCombined(): void
    {
        $this->assertSame('Cafe niniio', $this->helper->cleanName('  Café, niñío.  '));
    }

    public function testGetImagePath(): void
    {
        $this->assertSame($this->tempDir.'/public/wp_images/abc123.jpg', $this->helper->getImagePath('abc123'));
    }

    public function testGetRootDir(): void
    {
        $this->assertSame($this->tempDir.'/public/wp_images', $this->helper->getRootDir());
    }

    public function testGetIntelUrl(): void
    {
        $this->assertSame('https://intel.ingress.com/intel', $this->helper->getIntelUrl());
    }

    public function testFindImageReturnsFalseForNullId(): void
    {
        $this->assertFalse($this->helper->findImage(null));
    }

    public function testFindImageReturnsFalseWhenFileDoesNotExist(): void
    {
        $this->assertFalse($this->helper->findImage('nonexistent-guid'));
    }

    public function testFindImageReturnsPathWhenFileExists(): void
    {
        $wpId = 'abc123';
        $imagePath = $this->tempDir.'/public/wp_images/'.$wpId.'.jpg';
        file_put_contents($imagePath, 'fake-jpeg');

        $result = $this->helper->findImage($wpId);

        $this->assertSame($imagePath, $result);
    }
}
