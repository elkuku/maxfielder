<?php

namespace App\Service;

use DirectoryIterator;
use Elkuku\MaxfieldParser\MaxfieldParser;
use Elkuku\MaxfieldParser\Type\MaxField;
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

    public function getMaxfieldVersion(): int
    {
        return $this->maxfieldVersion;
    }
}
