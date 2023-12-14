<?php

namespace App\Service;

use App\Type\AgentKeyInfo;

class IngressHelper
{
    public function __construct(
        private readonly WayPointHelper $wayPointHelper,
    )
    {
    }

    /**
     * @return array<int, AgentKeyInfo>
     */
    public function parseKeysString(string $string): array
    {
        $keys = [];

        $lines = explode("\r\n", $string);

        for ($i = 1; $i < count($lines); $i++) {
            $k = new AgentKeyInfo;
            $data = explode("\t", $lines[$i]);
            $k->name = $this->wayPointHelper->cleanName($data[0]);
            $k->link = $data[1];
            $k->guid = $data[2];
            $k->count = (int)$data[3];
            $k->capsules = $data[4];

            $keys[] = $k;
        }

        return $keys;
    }
}