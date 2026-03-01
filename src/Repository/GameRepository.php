<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 *
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method list<Game> findAll()
 * @method list<Game> findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function save(Game $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Game $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(int $id): ?Game
    {
        return $this->find($id);
    }

    public function findByName(string $name): ?Game
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * @return list<Game>
     */
    public function findAllGames(): array
    {
        return $this->findAll();
    }

    /**
     * @return list<Game>
     */
    public function findGamesPaginated(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        return $this->createQueryBuilder('g')
            ->orderBy('g.game_id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalCount(): int
    {
        return (int) $this->createQueryBuilder('g')
            ->select('COUNT(g.game_id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Game>
     */
    public function findByGenre(string $genre): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.genre = :genre')
            ->setParameter('genre', $genre)
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Game>
     */
    public function searchByName(string $keyword): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.name LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function updateGameName(Game $game, string $newName, bool $flush = true): void
    {
        $game->setName($newName);
        $this->save($game, $flush);
    }

    public function updateGameGenre(Game $game, ?string $genre, bool $flush = true): void
    {
        $game->setGenre($genre);
        $this->save($game, $flush);
    }

    public function updateGameDescription(Game $game, ?string $description, bool $flush = true): void
    {
        $game->setDescription($description);
        $this->save($game, $flush);
    }

    public function deleteById(int $id, bool $flush = true): bool
    {
        $game = $this->find($id);
        if ($game) {
            $this->remove($game, $flush);
            return true;
        }
        return false;
    }

    /**
     * @param list<int> $gameIds
     */
    public function deleteMultiple(array $gameIds, bool $flush = true): int
    {
        $count = 0;
        foreach ($gameIds as $id) {
            if ($this->deleteById($id, false)) {
                $count++;
            }
        }
        if ($flush && $count > 0) {
            $this->getEntityManager()->flush();
        }
        return $count;
    }

    public function gameExists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * @return list<Game>
     */
    public function findGamesByTournament(int $tournamentId): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.tournaments', 't')
            ->andWhere('t.tournament_id = :tournamentId')
            ->setParameter('tournamentId', $tournamentId)
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
