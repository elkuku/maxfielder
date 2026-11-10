<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WaypointRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Stringable;

#[Entity(repositoryClass: WaypointRepository::class)]
class Waypoint implements Stringable
{
    #[Column, Id, GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    #[Column]
    private string $name = '';

    /**
     * DECIMAL columns hydrate as numeric strings; the accessors convert to and from float.
     */
    #[Column(type: 'decimal', precision: 10, scale: 6)]
    private string $lat = '0';

    #[Column(type: 'decimal', precision: 10, scale: 6)]
    private string $lon = '0';

    #[Column(length: 100, nullable: true)]
    private ?string $guid = '';

    #[Column(length: 255, nullable: true)]
    private ?string $image = null;

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLat(): ?float
    {
        return (float) $this->lat;
    }

    public function setLat(float $lat): self
    {
        $this->lat = (string) $lat;

        return $this;
    }

    public function getLon(): ?float
    {
        return (float) $this->lon;
    }

    public function setLon(float $lon): self
    {
        $this->lon = (string) $lon;

        return $this;
    }

    public function getGuid(): ?string
    {
        return $this->guid;
    }

    public function setGuid(string $guid): self
    {
        $this->guid = $guid;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }
}
