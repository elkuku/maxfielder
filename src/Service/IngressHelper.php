<?php

namespace App\Service;

use App\Type\AgentKeyInfo;
use App\Type\WaypointMap;

readonly class IngressHelper
{
    public function __construct(
        private WayPointHelper $wayPointHelper,
    ) {}

    /**
     * @return array<int, AgentKeyInfo>
     */
    public function parseKeysString(string $string): array
    {
        $keys = [];

        if (strpos($string, "\r\n")) {
            $lines = explode("\r\n", $string);
        } else {
            $lines = explode("\n", $string);
        }

        for ($i = 1, $iMax = count($lines); $i < $iMax; $i++) {
            $k = new AgentKeyInfo;
            $data = explode("\t", $lines[$i]);
            if (count($data) !== 5) {
                throw new \InvalidArgumentException('Invalid keys string!');
            }
            $k->name = $this->wayPointHelper->cleanName($data[0]);
            $k->link = $data[1];
            $k->guid = $data[2];
            $k->count = (int)$data[3];
            $k->capsules = $data[4];

            $keys[] = $k;
        }

        return $keys;
    }

    /**
     * @param WaypointMap[] $waypoints
     * @return AgentKeyInfo[]
     */
    public function getExistingKeysForMaxfield(array $waypoints, string $keys): array
    {
        $existingKeys = [];
        $parsedKeys = $this->parseKeysString($keys);

        foreach ($waypoints as $waypoint) {
            foreach ($parsedKeys as $parsedKey) {
                if ($parsedKey->guid === $waypoint->guid) {
                    $existingKeys[] = $parsedKey;
                }
            }
        }

        return $existingKeys;
    }
}
