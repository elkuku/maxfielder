<?php

namespace App\Repository;

use App\Entity\Waypoint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Waypoint|null find($id, $lockMode = null, $lockVersion = null)
 * @method Waypoint|null findOneBy(array $criteria, array $orderBy = null)
 * @method Waypoint[]    findAll()
 * @method Waypoint[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<WaypointRepository>
 */
class WaypointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        /**
         * @var class-string<WaypointRepository>
         */
        $className = Waypoint::class;
        parent::__construct($registry, $className);
    }

    /**
     * @return Waypoint[] Returns an array of Waypoint objects
     */
    public function findById(int $id): array
    {
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
        return $this->createQueryBuilder('w')
            ->select('w.id, w.guid, w.name, w.lat, w.lon as lng')
            ->andWhere('w.id IN (:val)')
            ->setParameter('val', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Waypoint[]
     */
    public function findLatLon(): array
    {
        $result = $this->createQueryBuilder('w')
            ->select("CONCAT(w.lat, ',', w.lon) AS lat_lon")
            ->getQuery()
            ->getResult();

        return array_column((array)$result, 'lat_lon');
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
