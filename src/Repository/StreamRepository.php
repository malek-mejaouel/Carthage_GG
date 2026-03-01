<?php

namespace App\Repository;

use App\Entity\Stream;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stream>
 */
class StreamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stream::class);
    }

    /** @return list<Stream> */
    public function findLatest(int $limit = 20): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.updated_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Stream> */
    public function findLive(int $limit = 20): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.is_live = :live')->setParameter('live', true)
            ->orderBy('s.updated_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
