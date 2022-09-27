<?php

namespace App\Repository;

use App\Entity\Maxfield;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
         * @var class-string<MaxfieldRepository>
         */
        $className = Maxfield::class;
        parent::__construct($registry, $className);
    }

    // /**
    //  * @return Maxfield[] Returns an array of Maxfield objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Maxfield
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
