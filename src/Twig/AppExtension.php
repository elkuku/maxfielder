<?php

namespace App\Twig;

use App\Entity\User;
use App\Entity\Waypoint;
use App\Service\WayPointHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(private readonly WayPointHelper $wayPointHelper)
    {
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
            new TwigFunction('hasImage', [$this, 'hasImage']),
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
        return array_search($value, User::ROLES, true) ?: '';
    }

    public function hasImage(Waypoint $waypoint): bool
    {
        return (bool)$this->wayPointHelper->findImage($waypoint->getGuid());
    }
}
