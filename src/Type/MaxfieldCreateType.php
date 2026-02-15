<?php

declare(strict_types=1);

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
        /** @var int|string|null $num */
        $num = $this->playersNum;

        return $num ? (int) $num : 1;
    }

    public function getProjectName(): string
    {
        return uniqid().'-'.(new AsciiSlugger())->slug($this->buildName);
    }
}
