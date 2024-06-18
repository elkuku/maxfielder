<?php

namespace App\Entity;

use App\Enum\MapBoxProfilesEnum;
use App\Enum\MapBoxStylesEnum;
use App\Enum\MapProvidersEnum;
use App\Repository\UserRepository;
use App\Settings\UserSettings;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

#[Entity(repositoryClass: UserRepository::class)]
#[Table(name: 'system_user')]
#[UniqueEntity(fields: 'identifier', message: 'This identifier is already in use')]
class User implements UserInterface, Stringable
{
    final public const array ROLES
        = [
            'user' => 'ROLE_USER',
            'agent' => 'ROLE_AGENT',
            'admin' => 'ROLE_ADMIN',
        ];

    #[Column, Id, GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = 0;

    #[Column(unique: true), NotBlank]
    private string $identifier = '*';

    /**
     * @var array<string>
     */
    #[Column(type: Types::JSON)]
    private array $roles = [];

    /**
     * @var array<string>|null
     */
    #[Column(type: Types::JSON, nullable: true)]
    private ?array $params = [];

    #[Column(length: 100, nullable: true)]
    private ?string $googleId = null;

    #[Column(nullable: true)]
    private ?int $gitHubId = null;

    /**
     * @var Collection<int, Maxfield>
     */
    #[OneToMany(mappedBy: 'owner', targetEntity: Maxfield::class)]
    private Collection $maxfields;

    /**
     * @var Collection<int, Maxfield>
     */
    #[ManyToMany(targetEntity: Maxfield::class)]
    private Collection $favourites;

    public function __construct()
    {
        $this->maxfields = new ArrayCollection();
        $this->favourites = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->identifier;
    }

    /**
     * @return array{
     *     id: integer|null,
     *     identifier: string|null
     * }
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'identifier' => $this->identifier,
        ];
    }

    /**
     * @param array{
     *     id: int|null,
     *     identifier: string|null
     * } $data
     */
    public function __unserialize(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->identifier = (string)($data['identifier'] ?? null);
    }

    public function eraseCredentials(): void
    {
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantee every user at least has ROLE_USER
        $roles[] = self::ROLES['user'];

        return array_unique($roles);
    }

    /**
     * @param array<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return array<string>|null
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

    public function getParam(string $name): string
    {
        return $this->params && array_key_exists($name, $this->params)
            ? $this->params[$name] ?: ''
            : '';
    }

    public function getUserParams(): UserSettings
    {
        static $settings;

        if ($settings) {
            return $settings;
        }

        $settings = new UserSettings();

        $settings->agentName = $this->getParam('agentName');
        $settings->lat = (float)$this->getParam('lat');
        $settings->lon = (float)$this->getParam('lon');
        $settings->zoom = (int)$this->getParam('zoom');
        $settings->mapboxApiKey = $this->getParam('mapboxApiKey');
        $settings->defaultStyle = $this->getParam('defaultStyle')
            ? MapBoxStylesEnum::tryFrom($this->getParam('defaultStyle'))
            : MapBoxStylesEnum::Standard;
        $settings->defaultProfile = $this->getParam('defaultProfile')
            ? MapBoxProfilesEnum::tryFrom($this->getParam('defaultProfile'))
            : MapBoxProfilesEnum::Driving;
        $settings->mapProvider = $this->getParam('mapProvider')
            ? MapProvidersEnum::tryFrom($this->getParam('mapProvider'))
            : MapProvidersEnum::leaflet;

        return $settings;
    }

    /**
     * @param array<string> $params
     */
    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @todo this method is required by the the rememberMe functionality :(
     */
    public function getPassword(): ?string
    {
        return null;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getGitHubId(): ?int
    {
        return $this->gitHubId;
    }

    public function setGitHubId(?int $gitHubId): self
    {
        $this->gitHubId = $gitHubId;

        return $this;
    }

    /**
     * @return Collection<int, Maxfield>
     */
    public function getMaxfields(): Collection
    {
        return $this->maxfields;
    }

    public function addMaxfield(Maxfield $maxfield): self
    {
        if (!$this->maxfields->contains($maxfield)) {
            $this->maxfields[] = $maxfield;
            $maxfield->setOwner($this);
        }

        return $this;
    }

    public function removeMaxfield(Maxfield $maxfield): self
    {
        if ($this->maxfields->removeElement($maxfield)) {
            // set the owning side to null (unless already changed)
            if ($maxfield->getOwner() === $this) {
                $maxfield->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Maxfield>
     */
    public function getFavourites(): Collection
    {
        return $this->favourites;
    }

    public function addFavourite(Maxfield $favourite): self
    {
        if (!$this->favourites->contains($favourite)) {
            $this->favourites->add($favourite);
        }

        return $this;
    }

    public function removeFavourite(Maxfield $favourite): self
    {
        $this->favourites->removeElement($favourite);

        return $this;
    }

    /**
     * @return bool the new state
     */
    public function toggleFavourite(Maxfield $favourite): bool
    {
        if ($this->favourites->contains($favourite)) {
            $this->favourites->removeElement($favourite);

            return false;
        }

        $this->favourites->add($favourite);

        return true;
    }
}
