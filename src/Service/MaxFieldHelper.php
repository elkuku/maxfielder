<?php

namespace App\Service;

use DirectoryIterator;
use Elkuku\MaxfieldParser\MaxfieldParser;
use Elkuku\MaxfieldParser\Type\MaxField;

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

    public function getMaxfieldVersion(): int
    {
        return $this->maxfieldVersion;
    }
}
