<?php

declare(strict_types=1);

namespace App\Tests\Service;

use InvalidArgumentException;
use App\Service\MaxFieldHelper;
use Elkuku\MaxfieldParser\MaxfieldParser;
use Elkuku\MaxfieldParser\Type\MaxField;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

final class MaxFieldHelperTest extends TestCase
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

        $this->assertSame(6, $helper->getMaxfieldVersion());
    }

    public function testGetMovieSizeReturnsNaForNonExistentPath(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        $this->assertSame('n/a', $helper->getMovieSize('nonexistent'));
    }

    public function testFilesFinishedReturnsFalseForNonExistentPath(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        $this->assertFalse($helper->filesFinished('nonexistent'));
    }

    public function testFramesDirCountReturnsNaForNonExistentPath(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        $this->assertSame('n/a', $helper->framesDirCount('nonexistent'));
    }

    public function testGetPreviewImageReturnsEmptyForNonExistentPath(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        $this->assertSame('', $helper->getPreviewImage('nonexistent'));
    }

    public function testGetWaypointCountReturnsZeroForNonExistentPath(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        $this->assertSame(0, $helper->getWaypointCount('nonexistent'));
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

        $this->assertSame(['alpha', 'bravo'], $list);
    }

    public function testGetLogReturnsContents(): void
    {
        $item = 'my-field';
        $root = $this->tempDir.'/public/maxfields/'.$item;
        mkdir($root, 0777, true);
        file_put_contents($root.'/log.txt', 'hello log');

        $helper = new MaxFieldHelper($this->tempDir, 6);

        $this->assertSame('hello log', $helper->getLog($item));
    }

    public function testGetLogRedactsRootDir(): void
    {
        $item = 'my-field';
        $root = $this->tempDir.'/public/maxfields/'.$item;
        mkdir($root, 0777, true);
        file_put_contents($root.'/log.txt', $this->tempDir.'/public/maxfields/secret-path');

        $helper = new MaxFieldHelper($this->tempDir, 6);
        $log = $helper->getLog($item);

        $this->assertStringNotContainsString($this->tempDir, (string) $log);
        $this->assertStringContainsString('...', (string) $log);
    }

    public function testFilesFinishedReturnsTrueWhenFileExists(): void
    {
        $item = 'my-field';
        $root = $this->tempDir.'/public/maxfields/'.$item;
        mkdir($root, 0777, true);
        file_put_contents($root.'/key_preparation.txt', '');

        $helper = new MaxFieldHelper($this->tempDir, 6);

        $this->assertTrue($helper->filesFinished($item));
    }

    public function testFramesDirCountReturnsActualCount(): void
    {
        $item = 'my-field';
        $framesDir = $this->tempDir.'/public/maxfields/'.$item.'/frames';
        mkdir($framesDir, 0777, true);
        file_put_contents($framesDir.'/a.png', '');
        file_put_contents($framesDir.'/b.png', '');

        $helper = new MaxFieldHelper($this->tempDir, 6);

        $this->assertSame('2', $helper->framesDirCount($item));
    }

    public function testGetMovieSizeReturnsFormattedSize(): void
    {
        $item = 'my-field';
        $root = $this->tempDir.'/public/maxfields/'.$item;
        mkdir($root, 0777, true);
        file_put_contents($root.'/plan_movie.gif', str_repeat('x', 1024));

        $helper = new MaxFieldHelper($this->tempDir, 6);

        $this->assertSame('1 KB', $helper->getMovieSize($item));
    }

    public function testGetMovieSizeReturnsZeroBytesForEmptyFile(): void
    {
        $item = 'my-field';
        $root = $this->tempDir.'/public/maxfields/'.$item;
        mkdir($root, 0777, true);
        file_put_contents($root.'/plan_movie.gif', '');

        $helper = new MaxFieldHelper($this->tempDir, 6);

        $this->assertSame('0 B', $helper->getMovieSize($item));
    }

    public function testGetPreviewImageReturnsWebPathWhenExists(): void
    {
        $item = 'my-field';
        $root = $this->tempDir.'/public/maxfields/'.$item;
        mkdir($root, 0777, true);
        file_put_contents($root.'/link_map.png', '');

        $helper = new MaxFieldHelper($this->tempDir, 6);

        $this->assertSame('maxfields/my-field/link_map.png', $helper->getPreviewImage($item));
    }

    public function testGetWaypointCountReturnsLineCount(): void
    {
        $item = 'my-field';
        $root = $this->tempDir.'/public/maxfields/'.$item;
        mkdir($root, 0777, true);
        file_put_contents($root.'/portals.txt', "portal 1\nportal 2\nportal 3");

        $helper = new MaxFieldHelper($this->tempDir, 6);

        $this->assertSame(3, $helper->getWaypointCount($item));
    }

    public function testGetWaypointsIdMapReturnsMappedData(): void
    {
        $item = 'my-field';
        $root = $this->tempDir.'/public/maxfields/'.$item;
        mkdir($root, 0777, true);
        file_put_contents($root.'/portals_id_map.csv', "0,42,guid-abc,Portal Alpha\n1,43,guid-def,Portal Beta\n");

        $helper = new MaxFieldHelper($this->tempDir, 6);
        $map = $helper->getWaypointsIdMap($item);

        $this->assertCount(2, $map);
        $this->assertSame(0, $map[0]->mapNo);
        $this->assertSame(42, $map[0]->dbId);
        $this->assertSame('guid-abc', $map[0]->guid);
        $this->assertSame('Portal Alpha', $map[0]->name);
        $this->assertSame(1, $map[1]->mapNo);
        $this->assertSame('Portal Beta', $map[1]->name);
    }

    public function testGetWaypointsIdMapThrowsForMissingFile(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        $this->expectException(InvalidArgumentException::class);

        $helper->getWaypointsIdMap('nonexistent');
    }

    public function testParsePlanResultsExtractsCorrectValues(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        $logContent = <<<LOG
Some output here...
===============================
Maxfield Plan Results:
portals = 36
links = 94
fields = 84
max keys needed = 10
AP from portals = 63000
AP from links = 29422
AP from fields = 105000
TOTAL AP = 197422
===============================
Optimizing agent link assignments...
LOG;

        $results = $helper->parsePlanResults($logContent);

        $this->assertNotNull($results);
        $this->assertSame(36, $results['portals']);
        $this->assertSame(94, $results['links']);
        $this->assertSame(84, $results['fields']);
        $this->assertSame(10, $results['max_keys_needed']);
        $this->assertSame(63000, $results['ap_from_portals']);
        $this->assertSame(29422, $results['ap_from_links']);
        $this->assertSame(105000, $results['ap_from_fields']);
        $this->assertSame(197422, $results['total_ap']);
    }

    public function testParsePlanResultsReturnsNullWhenSectionMissing(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        $logContent = 'Some random log output without the results section.';

        $results = $helper->parsePlanResults($logContent);

        $this->assertNull($results);
    }

    public function testParsePlanResultsReturnsPartialResultsWhenSectionIncomplete(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        $logContent = <<<LOG
===============================
Maxfield Plan Results:
portals = 36
===============================
LOG;

        $results = $helper->parsePlanResults($logContent);

        $this->assertNotNull($results);
        $this->assertSame(36, $results['portals']);
        $this->assertArrayNotHasKey('links', $results);
        $this->assertArrayNotHasKey('fields', $results);
    }

    public function testParsePlanResultsReturnsNullWhenOnlyHeader(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        $logContent = <<<LOG
===============================
Maxfield Plan Results:
===============================
LOG;

        $results = $helper->parsePlanResults($logContent);

        $this->assertNull($results);
    }

    public function testGetParserReturnsMaxfieldParser(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        $parser = $helper->getParser('some-item');

        $this->assertInstanceOf(MaxfieldParser::class, $parser);
    }

    public function testGetParserWithNoItemUsesRootDir(): void
    {
        $helper = new MaxFieldHelper($this->tempDir, 6);

        $parser = $helper->getParser();

        $this->assertInstanceOf(MaxfieldParser::class, $parser);
    }

    public function testGetMaxFieldParsesFixtureFiles(): void
    {
        $item = 'test-field';
        $root = $this->tempDir.'/public/maxfields/'.$item;
        mkdir($root, 0777, true);
        file_put_contents($root.'/portals.txt', "Portal Alpha; https://intel.ingress.com/intel?pll=48.0,11.0\n");
        file_put_contents($root.'/key_preparation.csv', "KeysNeeded, KeysHave, KeysRemaining, PortalNum, PortalName\n0, 0, 0, 0, Portal Alpha\n");
        file_put_contents($root.'/agent_assignments.csv', "LinkNum, Agent, OriginNum, OriginName, DestinationNum, DestinationName\n");
        file_put_contents($root.'/agent_key_preparation.csv', "Agent, PortalNum, PortalName, KeysNeeded\n");

        $helper = new MaxFieldHelper($this->tempDir, 6);
        $maxField = $helper->getMaxField($item);

        $this->assertInstanceOf(MaxField::class, $maxField);
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
