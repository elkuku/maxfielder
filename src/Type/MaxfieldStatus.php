<?php

namespace App\Type;

use App\Entity\Maxfield;
use App\Service\MaxFieldHelper;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class MaxfieldStatus
{
    private int $id;
    private string $name;
    private string $path;

    private string $status;
    private bool $filesFinished;
    private string $framesDirCount;
    private string $movieSize;
    private string $log;

    public function __construct(private readonly MaxFieldHelper $maxFieldHelper)
    {
    }

    public function fromMaxfield(Maxfield $maxfield): self
    {
        $this->id = (int)$maxfield->getId();
        $this->name = (string)$maxfield->getName();
        $this->path = (string)$maxfield->getPath();

        try {
            $this->log = (string)$this->maxFieldHelper->getLog($this->path);
            if (str_contains((string)$this->log, 'Total maxfield runtime')) {
                $this->status = 'finished';
            } elseif (str_contains((string)$this->log, 'Traceback (most recent call last):')) {
                $this->status = 'error';
            } else {
                $this->status = 'running';
            }
        } catch (FileNotFoundException) {
            $this->status = 'X';
        }

        $this->filesFinished = $this->maxFieldHelper
            ->filesFinished($this->path);

        $this->framesDirCount = $this->maxFieldHelper
            ->framesDirCount($this->path);

        $this->movieSize = $this->maxFieldHelper
            ->getMovieSize($this->path);

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getLog(): string
    {
        return $this->log;
    }

    public function isFilesFinished(): bool
    {
        return $this->filesFinished;
    }

    public function getFramesDirCount(): string
    {
        return $this->framesDirCount;
    }

    public function getMovieSize(): string
    {
        return $this->movieSize;
    }
}
