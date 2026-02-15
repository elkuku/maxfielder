<?php

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
        $waypoints = [];
        try {
            $items = json_decode(
                $data[$this->getType()],
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            throw new \UnexpectedValueException(
                'Invalid KExport JSON data'
            );
        }

        /** @var array<array{guid?: string, title?: string, lat: float, lng: float, image?: string}> $items */
        foreach ($items as $item) {
            if (!$guid = $item['guid'] ?? '') {
                continue;
            }

            if (!$title = $item['title'] ?? '') {
                continue;
            }

            $lat = $item['lat'];
            $lon = $item['lng'];
            $image = $item['image'] ?? '';

            if (isset($data['importImages']) && $data['importImages']) {
                $this->wayPointHelper->checkImage($guid, $image);
            }

            $waypoints[] = $this->createWayPoint($guid, $lat, $lon, $title, $image);
        }

        return $waypoints;
    }
}
