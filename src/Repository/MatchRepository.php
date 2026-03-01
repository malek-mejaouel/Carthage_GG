<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\GameMatch;
use App\Entity\Team;

/**
 * @extends ServiceEntityRepository<GameMatch>
 *
 * @method GameMatch|null find($id, $lockMode = null, $lockVersion = null)
 * @method GameMatch|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method list<GameMatch> findAll()
 * @method list<GameMatch> findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class MatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameMatch::class);
    }

    public function save(GameMatch $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(GameMatch $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(int $id): ?GameMatch
    {
        return $this->find($id);
    }

    /**
     * @return list<GameMatch>
     */
    public function findAllMatches(): array
    {
        return $this->findAll();
    }

    /**
     * @return list<GameMatch>
     */
    public function findMatchesPaginated(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        return $this->createQueryBuilder('m')
            ->orderBy('m.match_date', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalCount(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.match_id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<GameMatch>
     */
    public function findByTournament(int $tournamentId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.tournament = :tournamentId')
            ->setParameter('tournamentId', $tournamentId)
            ->orderBy('m.match_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<GameMatch>
     */
    public function findByGame(int $gameId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.game = :gameId')
            ->setParameter('gameId', $gameId)
            ->orderBy('m.match_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<GameMatch>
     */
    public function findByTeamA(int $teamId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.teamA = :teamId')
            ->setParameter('teamId', $teamId)
            ->orderBy('m.match_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<GameMatch>
     */
    public function findByTeamB(int $teamId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.teamB = :teamId')
            ->setParameter('teamId', $teamId)
            ->orderBy('m.match_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<GameMatch>
     */
    public function findByTeam(int $teamId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.teamA = :teamId OR m.teamB = :teamId')
            ->setParameter('teamId', $teamId)
            ->orderBy('m.match_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<GameMatch>
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.match_date >= :startDate AND m.match_date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('m.match_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function updateScores(GameMatch $match, int $scoreTeamA, int $scoreTeamB, bool $flush = true): void
    {
        $match->setScoreTeamA($scoreTeamA);
        $match->setScoreTeamB($scoreTeamB);
        $this->save($match, $flush);
    }

    public function updateMatchDate(GameMatch $match, ?\DateTimeInterface $date, bool $flush = true): void
    {
        $match->setMatchDate($date);
        $this->save($match, $flush);
    }

    public function deleteById(int $id, bool $flush = true): bool
    {
        $match = $this->find($id);
        if ($match) {
            $this->remove($match, $flush);
            return true;
        }
        return false;
    }

    public function matchExists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * @return list<GameMatch>
     */
    public function findUpcomingMatches(int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.match_date >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('m.match_date', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<GameMatch>
     */
    public function findPastMatches(int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.match_date < :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('m.match_date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<GameMatch>
     */
    public function findCompletedByTeam(Team $team): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.teamA = :team OR m.teamB = :team)')
            ->andWhere('m.score_team_a IS NOT NULL')
            ->andWhere('m.score_team_b IS NOT NULL')
            ->setParameter('team', $team)
            ->getQuery()
            ->getResult();
    }
    public function countPreviousMeetings(Team $teamA, Team $teamB): int
    {
        $res = $this->createQueryBuilder('m')
            ->select('COUNT(m.match_id)')
            ->where('(m.teamA = :teamA AND m.teamB = :teamB) OR (m.teamA = :teamB AND m.teamB = :teamA)')
            ->setParameter('teamA', $teamA)
            ->setParameter('teamB', $teamB)
            ->getQuery()
            ->getSingleScalarResult();
        return (int) $res;
    }
}
