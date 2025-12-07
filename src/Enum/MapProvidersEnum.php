<?php

namespace App\Enum;

enum MapProvidersEnum: string
{
    use ForSelectTrait;

    case leaflet = 'leaflet';
    case mapbox = 'mapbox';
}
