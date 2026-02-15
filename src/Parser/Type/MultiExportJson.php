<?php

declare(strict_types=1);

namespace App\Parser\Type;

use UnexpectedValueException;
use App\Parser\AbstractParser;
use JsonException;

class MultiExportJson extends AbstractParser
{
    protected function getType(): string
    {
        return 'multiexportjson';
    }

    /**
     * {@inheritDoc}
     */
    public function parse(array $data): array
    {
        $waypoints = [];
        try {
            $items = json_decode(
                $data[$this->getType()],
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            throw new UnexpectedValueException(
                'Invalid multiexport JSON data'
            );
        }

        /** @var array<array{guid?: string, title?: string, coordinates: array{lat: float, lng: float}, image?: string}> $items */
        foreach ($items as $item) {
            if (!$guid = $item['guid'] ?? '') {
                continue;
            }

            if (!$title = $item['title'] ?? '') {
                continue;
            }

            $lat = $item['coordinates']['lat'];
            $lon = $item['coordinates']['lng'];
            $image = $item['image'] ?? '';

            if (isset($data['importImages']) && $data['importImages']) {
                $this->wayPointHelper->checkImage($guid, $image);
            }

            $waypoints[] = $this->createWayPoint($guid, $lat, $lon, $title, $image);
        }

        return $waypoints;
    }
}
