<?php

namespace App\Service;

use App\Repository\ProductRepository;
use App\Entity\Product;

class ProductFeatureService
{
    public function __construct(private ProductRepository $products)
    {
    }

    /**
     * @return list<Product>
     */
    public function featured(int $limit = 8): array
    {
        $list = $this->products->findBy(['status' => 'active'], ['isFeatured' => 'DESC', 'createdAt' => 'DESC']);
        $scored = [];
        foreach ($list as $p) {
            $scored[] = [$p, $this->score($p)];
        }
        usort($scored, function (array $a, array $b) {
            return $b[1] <=> $a[1];
        });
        $result = [];
        foreach ($scored as $i => $pair) {
            if ($i >= $limit) {
                break;
            }
            $result[] = $pair[0];
        }
        return $result;
    }

    public function score(Product $p): float
    {
        $wFeatured = 2.0;
        $wSales = 1.5;
        $wRating = 1.2;
        $wRecency = 1.0;
        $wStock = 0.5;

        $featured = 0.0;
        if (method_exists($p, 'isFeatured')) {
            $featured = $p->isFeatured() ? 1.0 : 0.0;
        } elseif (method_exists($p, 'getIsFeatured')) {
            $featured = ((int) $p->getIsFeatured()) ? 1.0 : 0.0;
        }
        $sales = method_exists($p, 'getSalesCount') ? (float) $p->getSalesCount() : 0.0;
        $rating = method_exists($p, 'getAverageRating') ? (float) $p->getAverageRating() : 0.0;
        $created = method_exists($p, 'getCreatedAt') ? $p->getCreatedAt() : null;
        $recency = 0.0;
        if ($created instanceof \DateTimeInterface) {
            $days = max(1, (int) ((time() - $created->getTimestamp()) / 86400));
            $recency = 1.0 / $days;
        }
        $stock = method_exists($p, 'getStock') ? (float) $p->getStock() : 0.0;
        $stockFactor = $stock > 0 ? 1.0 : 0.0;

        return $wFeatured * $featured
            + $wSales * $sales
            + $wRating * $rating
            + $wRecency * $recency
            + $wStock * $stockFactor;
    }
}
