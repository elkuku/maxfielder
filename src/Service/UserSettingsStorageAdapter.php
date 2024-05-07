<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Jbtronics\SettingsBundle\Storage\StorageAdapterInterface;
use Symfony\Bundle\SecurityBundle\Security;

readonly class UserSettingsStorageAdapter implements StorageAdapterInterface
{
    public function __construct(
        private Security               $security,
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @param array<string> $data
     * @param array<string> $options
     */
    public function save(string $key, array $data, array $options = []): void
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $user->setParams($data);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @param array<string> $options
     * @return array<string>|null
     */
    public function load(string $key, array $options = []): ?array
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (null === $user) {
            return [];
        }

        return $user->getParams();
    }
}