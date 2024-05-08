<?php

namespace App\Enum;

enum MapBoxStylesEnum: string
{
    use ForSelectTrait;

    case Standard = 'mapbox/standard';
    case Standard_Clear = 'nikp3h/clvm8zx18043s01ph1xmu05dd';
    case Streets = 'mapbox/streets-v12';
    case Outdoors = 'mapbox/outdoors-v12';
    case Light = 'mapbox/light-v11';
    case Dark = 'mapbox/dark-v11';
    case Satellite = 'mapbox/satellite-v9';
    case Satellite_Streets = 'mapbox/satellite-streets-v12';
    case Navigation_Day = 'mapbox/navigation-day-v1';
    case Navigation_Night = 'mapbox/navigation-night-v1';
}
