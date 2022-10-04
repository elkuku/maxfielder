<?php

namespace App\Service;

use DirectoryIterator;
use Elkuku\MaxfieldParser\MaxfieldParser;
use Elkuku\MaxfieldParser\Type\MaxField;
use FilesystemIterator;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class MaxFieldHelper
{
    private string $rootDir;

    public function __construct(
        string $projectDir,
        private readonly int $maxfieldVersion
    ) {
        $this->rootDir = $projectDir.'/public/maxfields';
    }

    /**
     * @return array<string>
     */
    public function getList(): array
    {
        $list = [];

        foreach (new DirectoryIterator($this->rootDir) as $fileInfo) {
            if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                $list[] = $fileInfo->getFilename();
            }
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

        return file_get_contents($path);
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
            $sz = 'BKMGTP';
            $decimals = 2;
            $size = filesize($path);
            $factor = floor(((strlen($size)) - 1) / 3);
            return sprintf("%.{$decimals}f", $size / pow(1024, $factor)) . @$sz[$factor];
        }

        return 'n/a';
    }

    public function getMaxfieldVersion(): int
    {
        return $this->maxfieldVersion;
    }
}
