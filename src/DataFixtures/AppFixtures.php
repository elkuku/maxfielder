<?php

namespace App\DataFixtures;

use App\Entity\Maxfield;
use App\Entity\User;
use App\Entity\Waypoint;
use App\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = (new User())
            ->setIdentifier('user');

        $manager->persist($user);

        $manager->persist(
            (new User())
                ->setIdentifier('admin')
                ->setRole(UserRole::ADMIN)
        );

        $manager->persist(
            (new Waypoint())
                ->setGuid('test')
        );

        $manager->persist(
            (new Maxfield())
                ->setName('test')
                ->setPath('test')
                ->setOwner($user)
        );

        $manager->flush();
    }
}
