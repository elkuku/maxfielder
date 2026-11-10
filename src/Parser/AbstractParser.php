<?php

declare(strict_types=1);

namespace App\Parser;

use App\Entity\Waypoint;
use App\Service\WayPointHelper;
use JsonException;
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

        if ('' === $type || '0' === $type) {
            throw new UnexpectedValueException('Type is not set in class '.self::class);
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

    /**
     * Decodes the JSON payload stored under this parser's type key.
     *
     * @param array<string> $data
     */
    protected function decodeJson(array $data, string $errorMessage): mixed
    {
        try {
            return json_decode($data[$this->getType()], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new UnexpectedValueException($errorMessage);
        }
    }

    /**
     * Creates a waypoint and imports its image first when the request asks for it.
     *
     * @param array<string> $data
     */
    protected function createImportedWayPoint(
        array $data,
        string $guid,
        float $lat,
        float $lon,
        string $name,
        string $image,
    ): Waypoint {
        if (isset($data['importImages']) && $data['importImages']) {
            $this->wayPointHelper->checkImage($guid, $image);
        }

        return $this->createWayPoint($guid, $lat, $lon, $name, $image);
    }

    protected function createWayPoint(
        string $guid,
        float $lat,
        float $lon,
        string $name,
        string $image,
    ): Waypoint {
        return new Waypoint()
            ->setGuid($guid)
            ->setName($name)
            ->setLat($lat)
            ->setLon($lon)
            ->setImage($image);
    }
}
