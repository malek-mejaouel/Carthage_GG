<?php

namespace App\Repository;

use App\Entity\TeamPlayer;
use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeamPlayer>
 *
 * @method TeamPlayer|null find($id, $lockMode = null, $lockVersion = null)
 * @method TeamPlayer|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method list<TeamPlayer> findAll()
 * @method list<TeamPlayer> findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class TeamPlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamPlayer::class);
    }

    public function save(TeamPlayer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TeamPlayer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(int $id): ?TeamPlayer
    {
        return $this->find($id);
    }

    /**
     * @return list<TeamPlayer>
     */
    public function findAllTeamPlayers(): array
    {
        return $this->findAll();
    }

    /**
     * @return list<TeamPlayer>
     */
    public function findByTeam(int $teamId): array
    {
        return $this->createQueryBuilder('tp')
            ->andWhere('tp.team = :teamId')
            ->setParameter('teamId', $teamId)
            ->orderBy('tp.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<TeamPlayer>
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('tp')
            ->andWhere('tp.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('tp.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<TeamPlayer>
     */
    public function findTeamPlayersPaginated(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        return $this->createQueryBuilder('tp')
            ->orderBy('tp.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalCount(): int
    {
        return (int) $this->createQueryBuilder('tp')
            ->select('COUNT(tp.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTeamPlayerCount(int $teamId): int
    {
        return (int) $this->createQueryBuilder('tp')
            ->select('COUNT(tp.id)')
            ->andWhere('tp.team = :teamId')
            ->setParameter('teamId', $teamId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Team>
     */
    public function getUserTeams(int $userId): array
    {
        return $this->createQueryBuilder('tp')
            ->select('DISTINCT tp.team')
            ->andWhere('tp.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function deleteById(int $id, bool $flush = true): bool
    {
        $teamPlayer = $this->find($id);
        if ($teamPlayer) {
            $this->remove($teamPlayer, $flush);
            return true;
        }
        return false;
    }

    /**
     * @param list<int> $teamPlayerIds
     */
    public function deleteMultiple(array $teamPlayerIds, bool $flush = true): int
    {
        $count = 0;
        foreach ($teamPlayerIds as $id) {
            if ($this->deleteById($id, false)) {
                $count++;
            }
        }
        if ($flush && $count > 0) {
            $this->getEntityManager()->flush();
        }
        return $count;
    }

    public function teamPlayerExists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    public function playerIsInTeam(int $userId, int $teamId): bool
    {
        return $this->findOneBy(['user' => $userId, 'team' => $teamId]) !== null;
    }

    public function deleteByTeam(int $teamId, bool $flush = true): int
    {
        $teamPlayers = $this->findByTeam($teamId);
        $count = count($teamPlayers);
        foreach ($teamPlayers as $tp) {
            $this->remove($tp, false);
        }
        if ($flush && $count > 0) {
            $this->getEntityManager()->flush();
        }
        return $count;
    }
}
