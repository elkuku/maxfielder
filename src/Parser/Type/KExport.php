<?php

declare(strict_types=1);

namespace App\Parser\Type;

use App\Entity\Waypoint;
use App\Parser\AbstractParser;

class KExport extends AbstractParser
{
    protected function getType(): string
    {
        return 'kexport';
    }

    /**
     * @param array<string> $data
     *
     * @return Waypoint[]
     */
    public function parse(array $data): array
    {
        /** @var array<array{guid?: string, title?: string, lat: float, lng: float, image?: string}> $items */
        $items = $this->decodeJson($data, 'Invalid KExport JSON data');

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
                $item['lat'],
                $item['lng'],
                $title,
                $item['image'] ?? ''
            );
        }

        return $waypoints;
    }
}
