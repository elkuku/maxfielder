<?php

namespace App\Service;

use App\Type\AgentKeyInfo;
use Elkuku\MaxfieldParser\Type\MaxField;

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

        if (strpos($string, "\r\n")) {
            $lines = explode("\r\n", $string);
        }else{
            $lines = explode("\n", $string);
        }

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

    /**
     * @return AgentKeyInfo[]
     */
    public function getExistingKeysForMaxfield(MaxField $maxfield, string $keys):array
    {
        $existingKeys = [];
        $parsedKeys = $this->parseKeysString($keys);

        foreach ($maxfield->keyPrep->getWayPoints() as $keyPrep) {
            foreach ($parsedKeys as $parsedKey) {
                // TODO check guid to avoid dupes
                if ($parsedKey->name === $keyPrep->name) {
                    $existingKeys[] = $parsedKey;
                }
            }
        }

        return $existingKeys;
    }
}