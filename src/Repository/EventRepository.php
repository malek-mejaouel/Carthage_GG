<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Count events at the same location that overlap the given time window.
     */
    public function countConflicting(Location $location, \DateTimeImmutable $start, ?\DateTimeImmutable $end = null): int
    {
        $end = $end ?? $start;

        $qb = $this->createQueryBuilder('e')
            ->select('count(e.id)')
            ->andWhere('e.location = :loc')
            ->andWhere('e.startAt < :end')
            ->andWhere('COALESCE(e.endAt, e.startAt) > :start')
            ->setParameter('loc', $location)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}

