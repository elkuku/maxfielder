<?php

namespace App\Repository;

use App\Entity\Waypoint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Waypoint>
 */
class WaypointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Waypoint::class);
    }

    /**
     * @return Waypoint[] Returns an array of Waypoint objects
     */
    public function findById(int $id): array
    {
        /** @var Waypoint[] */
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $id)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<int> $ids
     *
     * @return Waypoint[]
     */
    public function findByIds(array $ids): array
    {
        /** @var Waypoint[] */
        return $this->createQueryBuilder('w')
            ->andWhere('w.id IN (:val)')
            ->setParameter('val', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<int> $ids
     *
     * @return Waypoint[]
     */
    public function findDetailsByIds(array $ids): array
    {
        /** @var Waypoint[] */
        return $this->createQueryBuilder('w')
            ->select('w.id, w.guid, w.name, w.lat, w.lon as lng')
            ->andWhere('w.id IN (:val)')
            ->setParameter('val', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<string>
     */
    public function findLatLon(): array
    {
        /** @var array<array{lat_lon: string}> $result */
        $result = $this->createQueryBuilder('w')
            ->select("CONCAT(w.lat, ',', w.lon) AS lat_lon")
            ->getQuery()
            ->getResult();

        return array_column($result, 'lat_lon');
    }

    /**
     * 50.140165627475554,
     * 8.537063598632814,
     * 49.975734392872745,
     * 8.02207946777344
     *
     * @return Waypoint[]
     */
    public function findInBounds(
        float $latMax,
        float $lonMax,
        float $latMin,
        float $lonMin,
    ): array
    {
        /** @var Waypoint[] */
        return $this->createQueryBuilder('w')
            ->andWhere('w.lat >= :latMin')
            ->andWhere('w.lat <= :latMax')
            ->andWhere('w.lon >= :lonMin')
            ->andWhere('w.lon <= :lonMax')
            ->setParameter('latMin', $latMin)
            ->setParameter('latMax', $latMax)
            ->setParameter('lonMin', $lonMin)
            ->setParameter('lonMax', $lonMax)
            ->getQuery()
            ->getResult();
    }
}
