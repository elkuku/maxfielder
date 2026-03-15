<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\WayPointHelper;
use App\Entity\Waypoint;
use App\Service\MaxFieldGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class MaxFieldGeneratorTest extends TestCase
{
    private MaxFieldGenerator $generator;

    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/maxfielder_gen_test_'.uniqid();
        mkdir($this->tempDir.'/public/maxfields', 0777, true);

        $this->generator = new MaxFieldGenerator(
            $this->tempDir,
            '/usr/bin/maxfield',
            4,
            '',
            '',
            'test-container',
            'https://intel.ingress.com/intel',
            '0',
        );
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tempDir);
    }

    public function testConvertWayPointsToMaxFields(): void
    {
        $wp1 = new Waypoint();
        $wp1->setName('Portal Alpha');
        $wp1->setLat(48.123);
        $wp1->setLon(11.456);

        $wp2 = new Waypoint();
        $wp2->setName('Portal Beta');
        $wp2->setLat(49.789);
        $wp2->setLon(12.321);

        $result = $this->generator->convertWayPointsToMaxFields([$wp1, $wp2]);

        $lines = explode("\n", $result);
        $this->assertCount(2, $lines);
        $this->assertStringContainsString('Portal Alpha', $lines[0]);
        $this->assertStringContainsString('48.123,11.456', $lines[0]);
        $this->assertStringContainsString('https://intel.ingress.com/intel', $lines[0]);
        $this->assertStringContainsString('Portal Beta', $lines[1]);
    }

    public function testConvertWayPointsStripsSpecialChars(): void
    {
        $wp = new Waypoint();
        $wp->setName('Portal; With# Special');
        $wp->setLat(1.0);
        $wp->setLon(2.0);

        $result = $this->generator->convertWayPointsToMaxFields([$wp]);

        $this->assertStringContainsString('Portal With Special', $result);
        $this->assertStringNotContainsString(';', explode(';', $result)[0]);
    }

    public function testGetWaypointsMap(): void
    {
        $wp1 = new Waypoint();
        $wp1->setName('Portal A');
        $wp1->setGuid('guid-a');

        $wp2 = new Waypoint();
        $wp2->setName('Portal B');
        $wp2->setGuid('guid-b');

        $result = $this->generator->getWaypointsMap([$wp1, $wp2]);

        $this->assertCount(2, $result);
        $this->assertSame(0, $result[0][0]);
        $this->assertNull($result[0][1]);
        $this->assertSame('guid-a', $result[0][2]);
        $this->assertSame('Portal A', $result[0][3]);
        $this->assertSame(1, $result[1][0]);
        $this->assertSame('Portal B', $result[1][3]);
    }

    public function testGetWaypointsMapStripsSpecialChars(): void
    {
        $wp = new Waypoint();
        $wp->setName('Portal; With# Comma,');
        $wp->setGuid('guid');

        $result = $this->generator->getWaypointsMap([$wp]);

        $this->assertSame('Portal With Comma', $result[0][3]);
    }

    public function testGetImagePath(): void
    {
        $this->assertSame($this->tempDir.'/public/maxfields/my-field/link_map.png', $this->generator->getImagePath('my-field', 'link_map.png'));
    }

    public function testGetContentListReturnsFiles(): void
    {
        $item = 'my-field';
        $dir = $this->tempDir.'/public/maxfields/'.$item;
        mkdir($dir, 0777, true);
        file_put_contents($dir.'/portals.txt', '');
        file_put_contents($dir.'/log.txt', '');

        $list = $this->generator->getContentList($item);

        $this->assertContains('portals.txt', $list);
        $this->assertContains('log.txt', $list);
        $this->assertSame($list, array_values(array_unique($list)));
    }

    public function testGetContentListIsSorted(): void
    {
        $item = 'my-field';
        $dir = $this->tempDir.'/public/maxfields/'.$item;
        mkdir($dir, 0777, true);
        file_put_contents($dir.'/z_file.txt', '');
        file_put_contents($dir.'/a_file.txt', '');

        $list = $this->generator->getContentList($item);

        $sorted = $list;
        sort($sorted);
        $this->assertSame($sorted, $list);
    }

    public function testRemoveDeletesDirectory(): void
    {
        $item = 'to-remove';
        $dir = $this->tempDir.'/public/maxfields/'.$item;
        mkdir($dir, 0777, true);
        file_put_contents($dir.'/portals.txt', '');

        $this->generator->remove($item);

        $this->assertDirectoryDoesNotExist($dir);
    }

    public function testFindFramesReturnsZeroForNonExistentPath(): void
    {
        $this->assertSame(0, $this->generator->findFrames('nonexistent'));
    }

    public function testFindFramesReturnsHighestFrameNumber(): void
    {
        $item = 'my-field';
        $framesDir = $this->tempDir.'/public/maxfields/'.$item.'/frames';
        mkdir($framesDir, 0777, true);
        file_put_contents($framesDir.'/frame_00001.png', '');
        file_put_contents($framesDir.'/frame_00005.png', '');
        file_put_contents($framesDir.'/frame_00003.png', '');

        $this->assertSame(5, $this->generator->findFrames($item));
    }

    public function testFindFramesIgnoresNonMatchingFiles(): void
    {
        $item = 'my-field';
        $framesDir = $this->tempDir.'/public/maxfields/'.$item.'/frames';
        mkdir($framesDir, 0777, true);
        file_put_contents($framesDir.'/something_else.png', '');

        $this->assertSame(0, $this->generator->findFrames($item));
    }

    public function testFindImageReturnsFalseForNullId(): void
    {
        $helper = new WayPointHelper($this->tempDir, 'https://intel.ingress.com/intel');
        $this->assertFalse($helper->findImage(null));
    }
}
