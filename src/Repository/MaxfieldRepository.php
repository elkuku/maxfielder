<?php

namespace App\Repository;

use App\Entity\Maxfield;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Maxfield>
 */
class MaxfieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Maxfield::class);
    }

    /**
     * @return Maxfield[]
     */
    public function search(string|null $search = null): array
    {
        /** @var Maxfield[] */
        return $this->createQueryBuilderSearch($search)
            ->getQuery()
            ->getResult();
    }

    public function createQueryBuilderSearch(string|null $search = null
    ): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->orderBy('m.name', 'ASC');

        if ($search) {
            $queryBuilder->andWhere('LOWER(m.name) LIKE LOWER(:search)')
                ->setParameter('search', '%'.$search.'%');
        }

        return $queryBuilder;
    }
}
