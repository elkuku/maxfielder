<?php

declare(strict_types=1);

namespace App\Parser\Type;

use App\Parser\AbstractParser;

class MultiExportJson extends AbstractParser
{
    protected function getType(): string
    {
        return 'multiexportjson';
    }

    public function parse(array $data): array
    {
        /** @var array<array{guid?: string, title?: string, coordinates: array{lat: float, lng: float}, image?: string}> $items */
        $items = $this->decodeJson($data, 'Invalid multiexport JSON data');

        $waypoints = [];

        foreach ($items as $item) {
            $guid = $item['guid'] ?? '';
            $title = $item['title'] ?? '';

            if (!$guid || !$title) {
                continue;
            }

            $waypoints[] = $this->createImportedWayPoint(
                $data,
                $guid,
                $item['coordinates']['lat'],
                $item['coordinates']['lng'],
                $title,
                $item['image'] ?? ''
            );
        }

        return $waypoints;
    }
}
