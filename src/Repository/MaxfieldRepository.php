<?php

namespace App\Repository;

use App\Entity\Maxfield;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Maxfield|null find($id, $lockMode = null, $lockVersion = null)
 * @method Maxfield|null findOneBy(array $criteria, array $orderBy = null)
 * @method Maxfield[]    findAll()
 * @method Maxfield[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<MaxfieldRepository>
 */
class MaxfieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        /**
         * @var class-string<MaxfieldRepository> $className
         */
        $className = Maxfield::class;
        parent::__construct($registry, $className);
    }

    /**
     * @return Maxfield[]
     */
    public function search(string $search = null): array
    {
        return $this->createQueryBuilderSearch($search)
            ->getQuery()
            ->getResult();
    }

    public function createQueryBuilderSearch(string $search = null
    ): QueryBuilder {
        $queryBuilder = $this->createQueryBuilder('m')
            ->orderBy('m.name', 'ASC');

        if ($search) {
            $queryBuilder->andWhere('LOWER(m.name) LIKE LOWER(:search)')
                ->setParameter('search', '%'.$search.'%');
        }

        return $queryBuilder;
    }
}
