<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return list<Product>
     */
    public function search(?string $q, ?int $categoryId, ?string $sort): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.status = :active')
            ->setParameter('active', 'active');

        if ($q) {
            $qb->andWhere('p.name LIKE :q OR p.description LIKE :q OR p.sku LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($categoryId) {
            $qb->andWhere('IDENTITY(p.category) = :cid')
               ->setParameter('cid', $categoryId);
        }

        // Always prioritize featured products first
        $qb->addOrderBy('p.isFeatured', 'DESC');
        switch ($sort) {
            case 'price_asc':
                $qb->addOrderBy('p.price', 'ASC');
                break;
            case 'price_desc':
                $qb->addOrderBy('p.price', 'DESC');
                break;
            case 'bestsellers':
                $qb->addOrderBy('p.salesCount', 'DESC');
                break;
            case 'featured':
                $qb->addOrderBy('p.createdAt', 'DESC');
                break;
            case 'newest':
            default:
                $qb->addOrderBy('p.createdAt', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<Product>
     */
    public function searchPaginated(?string $q, ?int $categoryId, ?string $sort, int $page, int $perPage, bool $onlyFeatured = false): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.status = :active')
            ->setParameter('active', 'active');
        if ($onlyFeatured) {
            $qb->andWhere('p.isFeatured = 1');
        }
        if ($q) {
            $qb->andWhere('p.name LIKE :q OR p.description LIKE :q OR p.sku LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }
        if ($categoryId) {
            $qb->andWhere('IDENTITY(p.category) = :cid')
               ->setParameter('cid', $categoryId);
        }
        $qb->addOrderBy('p.isFeatured', 'DESC');
        switch ($sort) {
            case 'price_asc':
                $qb->addOrderBy('p.price', 'ASC');
                break;
            case 'price_desc':
                $qb->addOrderBy('p.price', 'DESC');
                break;
            case 'bestsellers':
                $qb->addOrderBy('p.salesCount', 'DESC');
                break;
            case 'featured':
                $qb->addOrderBy('p.createdAt', 'DESC');
                break;
            case 'newest':
            default:
                $qb->addOrderBy('p.createdAt', 'DESC');
        }
        $offset = max(0, ($page - 1) * $perPage);
        $qb->setFirstResult($offset)->setMaxResults($perPage);
        return $qb->getQuery()->getResult();
    }

    public function countSearch(?string $q, ?int $categoryId, bool $onlyFeatured = false): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.status = :active')
            ->setParameter('active', 'active');
        if ($onlyFeatured) {
            $qb->andWhere('p.isFeatured = 1');
        }
        if ($q) {
            $qb->andWhere('p.name LIKE :q OR p.description LIKE :q OR p.sku LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }
        if ($categoryId) {
            $qb->andWhere('IDENTITY(p.category) = :cid')
               ->setParameter('cid', $categoryId);
        }
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
