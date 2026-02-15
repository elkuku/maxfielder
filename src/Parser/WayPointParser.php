<?php

declare(strict_types=1);

namespace App\Parser;

use App\Entity\Waypoint;
use App\Service\WayPointHelper;
use DirectoryIterator;
use UnexpectedValueException;

class WayPointParser
{
    public bool $processImages = false;

    public function __construct(private readonly WayPointHelper $wayPointHelper) {}

    /**
     * @param array<string> $data
     *
     * @return Waypoint[]
     */
    public function parse(array $data): array
    {
        foreach (new DirectoryIterator(__DIR__.'/Type') as $item) {
            if ($item->isDot()) {
                continue;
            }

            $className = '\\'.__NAMESPACE__.'\\Type\\'
                .basename($item->getFilename(), '.php');

            /**
             * @var AbstractParser $parser
             */
            $parser = new $className($this->wayPointHelper);

            if ($parser->supports($data)) {
                return $parser->parse($data);
            }
        }

        throw new UnexpectedValueException('No suitable parser found :(');
    }
}
