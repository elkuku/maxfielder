<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Maxfield;
use App\Entity\Waypoint;
use App\Service\MaxFieldHelper;
use App\Service\WayPointHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly WayPointHelper $wayPointHelper,
        private readonly MaxFieldHelper $maxFieldHelper,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('hasImage', $this->hasImage(...)),
            new TwigFunction('previewImage', $this->previewImage(...)),
            new TwigFunction('waypointCount', $this->waypointCount(...)),
        ];
    }

    public function hasImage(Waypoint $waypoint): bool
    {
        return (bool)$this->wayPointHelper->findImage($waypoint->getGuid());
    }

    public function previewImage(Maxfield $maxfield): string
    {
        return $this->maxFieldHelper->getPreviewImage($maxfield->getPath() ?? '')
            ?: 'images/no-preview.jpg';
    }

    public function waypointCount(Maxfield $maxfield): int
    {
        return $this->maxFieldHelper->getWaypointCount($maxfield->getPath() ?? '');
    }
}
