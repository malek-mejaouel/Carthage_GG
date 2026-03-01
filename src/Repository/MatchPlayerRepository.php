<?php

namespace App\Repository;

use App\Entity\MatchPlayer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MatchPlayer>
 *
 * @method MatchPlayer|null find($id, $lockMode = null, $lockVersion = null)
 * @method MatchPlayer|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method list<MatchPlayer> findAll()
 * @method list<MatchPlayer> findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class MatchPlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MatchPlayer::class);
    }

    public function save(MatchPlayer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MatchPlayer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(int $id): ?MatchPlayer
    {
        return $this->find($id);
    }

    /**
     * @return list<MatchPlayer>
     */
    public function findAllMatchPlayers(): array
    {
        return $this->findAll();
    }

    /**
     * @return list<MatchPlayer>
     */
    public function findByMatch(int $matchId): array
    {
        return $this->createQueryBuilder('mp')
            ->andWhere('mp.match = :matchId')
            ->setParameter('matchId', $matchId)
            ->orderBy('mp.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<MatchPlayer>
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('mp')
            ->andWhere('mp.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('mp.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<MatchPlayer>
     */
    public function findByTeam(int $teamId): array
    {
        return $this->createQueryBuilder('mp')
            ->andWhere('mp.team = :teamId')
            ->setParameter('teamId', $teamId)
            ->orderBy('mp.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<MatchPlayer>
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('mp')
            ->andWhere('mp.role = :role')
            ->setParameter('role', $role)
            ->orderBy('mp.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<MatchPlayer>
     */
    public function findMatchPlayersPaginated(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        return $this->createQueryBuilder('mp')
            ->orderBy('mp.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalCount(): int
    {
        return (int) $this->createQueryBuilder('mp')
            ->select('COUNT(mp.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function updateRole(MatchPlayer $matchPlayer, ?string $role, bool $flush = true): void
    {
        $matchPlayer->setRole($role);
        $this->save($matchPlayer, $flush);
    }

    public function deleteById(int $id, bool $flush = true): bool
    {
        $matchPlayer = $this->find($id);
        if ($matchPlayer) {
            $this->remove($matchPlayer, $flush);
            return true;
        }
        return false;
    }

    public function matchPlayerExists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * @return list<MatchPlayer>
     */
    public function findPlayersByMatchAndTeam(int $matchId, int $teamId): array
    {
        return $this->createQueryBuilder('mp')
            ->andWhere('mp.match = :matchId AND mp.team = :teamId')
            ->setParameter('matchId', $matchId)
            ->setParameter('teamId', $teamId)
            ->orderBy('mp.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function deleteByMatch(int $matchId, bool $flush = true): int
    {
        $matchPlayers = $this->findByMatch($matchId);
        $count = count($matchPlayers);
        foreach ($matchPlayers as $mp) {
            $this->remove($mp, false);
        }
        if ($flush && $count > 0) {
            $this->getEntityManager()->flush();
        }
        return $count;
    }
}
