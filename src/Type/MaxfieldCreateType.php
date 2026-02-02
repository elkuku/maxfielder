<?php

namespace App\Type;

use Symfony\Component\String\Slugger\AsciiSlugger;

final class MaxfieldCreateType
{
    public string $points;

    public string $buildName;

    /**
     * @todo can not type hint this :(
     * @see https://github.com/symfony/symfony/issues/50759
     */
    public mixed $playersNum = null;

    public bool $skipPlots = false;

    public bool $skipStepPlots = false;

    /**
     * @return array<int>
     */
    public function getPoints(): array
    {
        return array_map(intval(...), explode(',', $this->points));
    }

    public function getPlayersNum(): int
    {
        return $this->playersNum ? (int)$this->playersNum : 1;
    }

    public function getProjectName(): string
    {
        return uniqid().'-'.(new AsciiSlugger())->slug($this->buildName);
    }
}
