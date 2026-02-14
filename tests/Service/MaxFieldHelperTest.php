<?php

namespace App\Tests\Service;

use App\Service\MaxFieldHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class MaxFieldHelperTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/maxfielder_test_'.uniqid();
        mkdir($this->tempDir.'/public/maxfields', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testGetMaxfieldVersion(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        self::assertSame(6, $helper->getMaxfieldVersion());
    }

    public function testGetMovieSizeReturnsNaForNonExistentPath(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        self::assertSame('n/a', $helper->getMovieSize('nonexistent'));
    }

    public function testFilesFinishedReturnsFalseForNonExistentPath(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        self::assertFalse($helper->filesFinished('nonexistent'));
    }

    public function testFramesDirCountReturnsNaForNonExistentPath(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        self::assertSame('n/a', $helper->framesDirCount('nonexistent'));
    }

    public function testGetPreviewImageReturnsEmptyForNonExistentPath(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        self::assertSame('', $helper->getPreviewImage('nonexistent'));
    }

    public function testGetWaypointCountReturnsZeroForNonExistentPath(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        self::assertSame(0, $helper->getWaypointCount('nonexistent'));
    }

    public function testGetLogThrowsForMissingLog(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        $this->expectException(FileNotFoundException::class);

        $helper->getLog('nonexistent');
    }

    public function testGetListWithSubdirectories(): void
    {
        $root = $this->tempDir.'/public/maxfields';
        mkdir($root.'/bravo');
        mkdir($root.'/alpha');

        $helper = new MaxFieldHelper($this->tempDir, 6);
        $list = $helper->getList();

        self::assertSame(['alpha', 'bravo'], $list);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.') {
                continue;
            }
            if ($item === '..') {
                continue;
            }
            $path = $dir.'/'.$item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
