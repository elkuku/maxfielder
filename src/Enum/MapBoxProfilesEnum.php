<?php

declare(strict_types=1);

namespace App\Enum;

enum MapBoxProfilesEnum: string
{
    use ForSelectTrait;

    case Driving = 'mapbox/driving';
    case Walking = 'mapbox/walking';
    case Cycling = 'mapbox/cycling';
}
