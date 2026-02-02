<?php

namespace App\Service;

use App\Type\WaypointMap;
use DirectoryIterator;
use Elkuku\MaxfieldParser\MaxfieldParser;
use Elkuku\MaxfieldParser\Type\MaxField;
use FilesystemIterator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

readonly class MaxFieldHelper
{
    private string $rootDir;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        #[Autowire('%env(MAXFIELD_VERSION)%')] private int $maxfieldVersion
    )
    {
        $this->rootDir = $projectDir.'/public/maxfields';
    }

    /**
     * @return array<string>
     */
    public function getList(): array
    {
        $list = [];

        foreach (new DirectoryIterator($this->rootDir) as $fileInfo) {
            if (!$fileInfo->isDir()) {
                continue;
            }
            if ($fileInfo->isDot()) {
                continue;
            }
            $list[] = $fileInfo->getFilename();
        }

        sort($list);

        return $list;
    }

    public function getParser(string $item = ''): MaxfieldParser
    {
        $dir = $item ? $this->rootDir.'/'.$item : $this->rootDir;

        return new MaxfieldParser($dir);
    }

    public function getMaxField(string $item): MaxField
    {
        return $this->getParser()->parse($item);
    }

    public function getLog(string $item): bool|string
    {
        $path = $this->rootDir.'/'.$item.'/log.txt';

        if (false === file_exists($path)) {
            throw new FileNotFoundException();
        }

        return str_replace($this->rootDir, '...', file_get_contents($path));
    }

    public function filesFinished(string $item): bool
    {
        return file_exists($this->rootDir."/$item/key_preparation.txt");
    }

    public function framesDirCount(string $item): string
    {
        $path = $this->rootDir."/$item/frames";

        return (is_dir($path))
            ? (string)iterator_count(new FilesystemIterator($path))
            : 'n/a';
    }

    public function getMovieSize(string $item): string
    {
        $path = $this->rootDir."/$item/plan_movie.gif";

        if (file_exists($path)) {
            $bytes = filesize($path);
            $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
            $factor = (int)floor(log($bytes, 1024));
            return round($bytes / 1024 ** $factor, 2).' '.$sizes[$factor];
        }

        return 'n/a';
    }

    public function getMaxfieldVersion(): int
    {
        return $this->maxfieldVersion;
    }

    public function getPreviewImage(string $item): string
    {
        $path = $this->rootDir."/$item/link_map.png";
        $webPath = "maxfields/$item/link_map.png";

        return file_exists($path) ? $webPath : '';
    }

    public function getWaypointCount(string $item): int
    {
        $path = $this->rootDir."/$item/portals.txt";

        if (false === file_exists($path)) {
            return 0;
        }

        $contents = file($path, FILE_IGNORE_NEW_LINES);

        if (false === $contents) {
            throw new \UnexpectedValueException('Can not read file in '.$path);
        }

        return count($contents);
    }

    /**
     * TODO Move this somewhere else...
     *
     * @return WaypointMap[]
     */
    public function getWaypointsIdMap(string $item): array
    {
        $path = $this->rootDir."/$item/portals_id_map.csv";
        $map = [];
        if (false !== ($handle = fopen($path, 'r'))) {
            while (false !== ($data = fgetcsv($handle, 1000, ','))) {
                $waypoint = new WaypointMap();

                $waypoint->mapNo = (int)$data[0];
                $waypoint->dbId = (int)$data[1];
                $waypoint->guid = (string)$data[2];
                $waypoint->name = (string)$data[3];

                $map[] = $waypoint;
            }
            fclose($handle);
        } else {
            throw new \InvalidArgumentException('Can not open '.$path);
        }

        return $map;
    }
}
