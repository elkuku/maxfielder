<?php

declare(strict_types=1);

namespace App\Entity;

use Stringable;
use App\Repository\WaypointRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity(repositoryClass: WaypointRepository::class)]
class Waypoint implements Stringable
{
    #[Column, Id, GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    #[Column]
    private string $name = '';

    #[Column(type: 'decimal', precision: 10, scale: 6)]
    private float $lat = 0;

    #[Column(type: 'decimal', precision: 10, scale: 6)]
    private float $lon = 0;

    #[Column(length: 100, nullable: true)]
    private string $guid = '';

    #[Column(length: 255, nullable: true)]
    private ?string $image = null;

    public function __toString(): string
    {
        return (string)$this->getName();
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
        return $this->lat;
    }

    public function setLat(float $lat): self
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLon(): ?float
    {
        return $this->lon;
    }

    public function setLon(float $lon): self
    {
        $this->lon = $lon;

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
