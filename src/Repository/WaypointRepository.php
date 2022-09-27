<?php

namespace App\Repository;

use App\Entity\Waypoint;
use App\Helper\Paginator\PaginatorOptions;
use App\Helper\Paginator\PaginatorRepoTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
    // use PaginatorRepoTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Waypoint::class);
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
     * @return Paginator<Query>
     */
    public function getRawList(PaginatorOptions $options): Paginator
    {
        $criteria = $options->getCriteria();

        $query = $this->createQueryBuilder('w')
            ->orderBy('w.'.$options->getOrder(), $options->getOrderDir());

        if ($options->searchCriteria('name')) {
            $query->andWhere('LOWER(w.name) LIKE :name')
                ->setParameter(
                    'name',
                    '%'.strtolower($options->searchCriteria('name')).'%'
                );
        }

        $query = $query->getQuery();

        return $this->paginate(
            $query,
            $options->getPage(),
            $options->getLimit()
        );
    }
}
