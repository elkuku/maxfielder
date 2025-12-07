<?php

namespace App\Twig;

use App\Entity\Maxfield;
use App\Entity\Waypoint;
use App\Enum\UserRole;
use App\Service\MaxFieldHelper;
use App\Service\WayPointHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly WayPointHelper $wayPointHelper,
        private readonly MaxFieldHelper $maxFieldHelper,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('role_name', $this->getRoleName(...)),
            new TwigFilter('role_names', $this->getRoleNames(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('hasImage', $this->hasImage(...)),
            new TwigFunction('previewImage', $this->previewImage(...)),
            new TwigFunction('waypointCount', $this->waypointCount(...)),
        ];
    }

    /**
     * @param array<string> $values
     */
    public function getRoleNames(array $values): string
    {
        $roles = [];
        foreach ($values as $value) {
            $roles[] = $this->getRoleName($value);
        }

        return implode(', ', $roles);
    }

    public function getRoleName(string $value): string
    {
        return array_search($value, UserRole::cases(), true) ?: '';
    }

    public function hasImage(Waypoint $waypoint): bool
    {
        return (bool) $this->wayPointHelper->findImage($waypoint->getGuid());
    }

    public function previewImage(Maxfield $maxfield): string
    {
        return $this->maxFieldHelper->getPreviewImage($maxfield->getPath())
            ?: 'images/no-preview.jpg';
    }

    public function waypointCount(Maxfield $maxfield): int
    {
        return $this->maxFieldHelper->getWaypointCount($maxfield->getPath());
    }
}
