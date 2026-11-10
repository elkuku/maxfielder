<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Maxfield;
use App\Entity\Waypoint;
use App\Service\MaxFieldHelper;
use App\Service\WayPointHelper;
use Twig\Attribute\AsTwigFunction;

class AppExtension
{
    public function __construct(
        private readonly WayPointHelper $wayPointHelper,
        private readonly MaxFieldHelper $maxFieldHelper,
    ) {
    }

    #[AsTwigFunction(name: 'hasImage')]
    public function hasImage(Waypoint $waypoint): bool
    {
        return (bool) $this->wayPointHelper->findImage($waypoint->getGuid());
    }

    #[AsTwigFunction(name: 'previewImage')]
    public function previewImage(Maxfield $maxfield): string
    {
        return $this->maxFieldHelper->getPreviewImage($maxfield->getPath() ?? '')
            ?: 'images/no-preview.jpg';
    }

    #[AsTwigFunction(name: 'waypointCount')]
    public function waypointCount(Maxfield $maxfield): int
    {
        return $this->maxFieldHelper->getWaypointCount($maxfield->getPath() ?? '');
    }
}
