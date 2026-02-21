<?php

namespace App\Repository;

use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 *
 * @method Team|null find($id, $lockMode = null, $lockVersion = null)
 * @method Team|null findOneBy(array $criteria, array $orderBy = null)
 * @method Team[]    findAll()
 * @method Team[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    /**
     * CREATE: Save a new or existing team
     */
    public function save(Team $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * DELETE: Remove a team from the database
     */
    public function remove(Team $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * READ: Find team by ID
     */
    public function findById(int $id): ?Team
    {
        return $this->find($id);
    }

    /**
     * READ: Find team by name
     */
    public function findByTeamName(string $teamName): ?Team
    {
        return $this->findOneBy(['team_name' => $teamName]);
    }

    /**
     * READ: Find all teams
     */
    public function findAllTeams(): array
    {
        return $this->findAll();
    }

    /**
     * READ: Find teams with pagination
     */
    public function findTeamsPaginated(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('t')
            ->orderBy('t.team_id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * READ: Get total count of teams
     */
    public function getTotalCount(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.team_id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * READ: Find teams by user
     */
    public function findByUser(?int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.team_id', 'DESC')
            ->getQuery()
            ->getResult();
    }
    

    /**
     * READ: Find teams created after a specific date
     */
    public function findTeamsSince(?\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.creation_date >= :date')
            ->setParameter('date', $date)
            ->orderBy('t.creation_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * READ: Find teams by name (partial match)
     */
    public function searchByTeamName(string $keyword): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.team_name LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('t.team_name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * UPDATE: Update team name
     */
    public function updateTeamName(Team $team, string $newName, bool $flush = true): void
    {
        $team->setTeamName($newName);
        $this->save($team, $flush);
    }

    /**
     * UPDATE: Update team logo
     */
    public function updateTeamLogo(Team $team, ?string $logoPath, bool $flush = true): void
    {
        $team->setLogo($logoPath);
        $this->save($team, $flush);
    }

    /**
     * UPDATE: Update team creation date
     */
    public function updateCreationDate(Team $team, ?\DateTimeInterface $date, bool $flush = true): void
    {
        $team->setCreationDate($date);
        $this->save($team, $flush);
    }

    /**
     * DELETE: Delete team by ID
     */
    public function deleteById(int $id, bool $flush = true): bool
    {
        $team = $this->find($id);
        if ($team) {
            $this->remove($team, $flush);
            return true;
        }
        return false;
    }

    /**
     * DELETE: Delete multiple teams
     */
    public function deleteMultiple(array $teamIds, bool $flush = true): int
    {
        $count = 0;
        foreach ($teamIds as $id) {
            if ($this->deleteById($id, false)) {
                $count++;
            }
        }
        if ($flush && $count > 0) {
            $this->getEntityManager()->flush();
        }
        return $count;
    }

    /**
     * READ: Find teams with most players
     */
    public function findTeamsWithMostPlayers(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.teamPlayers', 'tp')
            ->select('t', 'COUNT(tp.id) as player_count')
            ->groupBy('t.team_id')
            ->orderBy('player_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * READ: Check if team exists
     */
    public function teamExists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * READ: Get team count by user
     */
    public function getTeamCountByUser(int $userId): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.team_id)')
            ->andWhere('t.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
   
}
