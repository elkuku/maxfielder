<?php

namespace App\Parser;

use App\Entity\Waypoint;
use App\Service\WayPointHelper;
use UnexpectedValueException;

abstract class AbstractParser
{
    public bool $processImages = false;

    public function __construct(protected WayPointHelper $wayPointHelper)
    {
    }

    abstract protected function getType(): string;

    /**
     * @param array<string> $data
     *
     * @return Waypoint[]
     */
    abstract public function parse(array $data): array;

    /**
     * @param array<string> $data
     */
    public function supports(array $data): bool
    {
        $type = $this->gettype();

        if (!$type) {
            throw new UnexpectedValueException(
                'Type is not set in class '.self::class
            );
        }

        return $this->check($type, $data);
    }

    /**
     * @param array<string> $data
     */
    protected function check(string $key, array $data): bool
    {
        return array_key_exists($key, $data) && $data[$key];
    }

    protected function createWayPoint(
        string $guid,
        float $lat,
        float $lon,
        string $name,
        string $image,
    ): Waypoint {
        return (new Waypoint())
            ->setGuid($guid)
            ->setName($name)
            ->setLat($lat)
            ->setLon($lon)
            ->setImage($image);
    }
}
