<?php

namespace App\Repository;

use App\Entity\Commentaire;
use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    /**
     * Find all comments for a specific news article
     * Returns comments sorted by newest first
     * 
     * @param News $news The news article to find comments for
     * @return Commentaire[] Array of comments
     */
    public function findByNews(News $news): array
    {
        return $this->findBy(
            ['news' => $news],
            ['date_commentaire' => 'DESC']
        );
    }

    /**
     * Count comments for a specific news article
     * 
     * @param News $news The news article to count comments for
     * @return int Number of comments
     */
    public function countByNews(News $news): int
    {
        return $this->count(['news' => $news]);
    }

    /**
     * Find all comments ordered by date (newest first)
     * 
     * @return Commentaire[] Array of all comments
     */
    public function findAllOrderedByDate(): array
    {
        return $this->findBy([], ['date_commentaire' => 'DESC']);
    }
}
