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
    public function createQueryBuilderSearch(string $search = null): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('m');

        if ($search) {
            $queryBuilder->andWhere('m.name = :search')
                ->setParameter('search', $search);
        }

        return $queryBuilder;
    }
}
