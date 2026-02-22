<?php

namespace App\Repository;

use App\Entity\Tournament;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tournament>
 *
 * @method Tournament|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tournament|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tournament[]    findAll()
 * @method Tournament[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TournamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournament::class);
    }

    public function save(Tournament $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Tournament $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(int $id): ?Tournament
    {
        return $this->find($id);
    }

    public function findByName(string $name): ?Tournament
    {
        return $this->findOneBy(['tournament_name' => $name]);
    }

    public function findAllTournaments(): array
    {
        return $this->findAll();
    }

    public function findTournamentsPaginated(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        return $this->createQueryBuilder('t')
            ->orderBy('t.start_date', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalCount(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.tournament_id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByGame(int $gameId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.game = :gameId')
            ->setParameter('gameId', $gameId)
            ->orderBy('t.start_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.start_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByLocation(string $location): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.location = :location')
            ->setParameter('location', $location)
            ->orderBy('t.start_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.start_date >= :startDate AND t.end_date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.start_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchByName(string $keyword): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.tournament_name LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('t.tournament_name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function updateTournamentName(Tournament $tournament, string $newName, bool $flush = true): void
    {
        $tournament->setTournamentName($newName);
        $this->save($tournament, $flush);
    }

    public function updatePrizePool(Tournament $tournament, ?string $prizePool, bool $flush = true): void
    {
        $tournament->setPrizePool($prizePool);
        $this->save($tournament, $flush);
    }

    public function updateLocation(Tournament $tournament, ?string $location, bool $flush = true): void
    {
        $tournament->setLocation($location);
        $this->save($tournament, $flush);
    }

    public function deleteById(int $id, bool $flush = true): bool
    {
        $tournament = $this->find($id);
        if ($tournament) {
            $this->remove($tournament, $flush);
            return true;
        }
        return false;
    }

    public function tournamentExists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    public function findUpcomingTournaments(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.start_date >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('t.start_date', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    public function sumPrizePool(): int|float
    {
        return $this->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.prize_pool), 0)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOngoingTournaments(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.start_date <= :now AND t.end_date >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('t.start_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCompletedTournaments(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.end_date < :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('t.end_date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
