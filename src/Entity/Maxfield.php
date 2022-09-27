<?php

namespace App\Entity;

use App\Repository\MaxfieldRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Elkuku\MaxfieldParser\Type\Waypoint;
use stdClass;

#[Entity(repositoryClass: MaxfieldRepository::class)]
class Maxfield
{
    #[Id, GeneratedValue(strategy: 'AUTO')]
    #[Column(type: Types::INTEGER)]
    private ?int $id = 0;

    #[Column(type: Types::STRING, length: 150)]
    private ?string $name;

    #[Column(length: 255, nullable: true)]
    private ?string $path = null;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'maxfields')]
    #[JoinColumn(nullable: false)]
    private ?User $owner;

    /**
     * @var array<string, array<Waypoint|\stdClass>>
     */
    #[Column(type: Types::JSON, nullable: true)]
    private array|stdClass|null $jsonData = null;

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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return array<string, array<Waypoint|\stdClass>>|null
     */
    public function getJsonData(): ?array
    {
        return $this->jsonData;
    }

    /**
     * @param array<string, array<Waypoint|\stdClass>>|null $jsonData
     */
    public function setJsonData(array|stdClass $jsonData): self
    {
        $this->jsonData = $jsonData;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;

        return $this;
    }
}
