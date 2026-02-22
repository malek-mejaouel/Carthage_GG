<?php

namespace App\Repository;

use App\Entity\FaceAuthentication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FaceAuthenticationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaceAuthentication::class);
    }

    public function findOneByUserId(int $userId): ?FaceAuthentication
    {
        return $this->createQueryBuilder('f')
            ->join('f.user', 'u')
            ->andWhere('u.id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

