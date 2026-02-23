<?php

namespace App\Repository;

use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    /**
     * Find a single Location within $radiusMeters of given lat/lng using Haversine formula.
     * Returns the nearest Location or null.
     */
    public function findNearby(float $lat, float $lng, int $radiusMeters = 50): ?Location
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT id, (
            6371000 * acos(
                cos(radians(:lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians(:lng)) +
                sin(radians(:lat)) * sin(radians(latitude))
            )
        ) AS distance
        FROM location
        WHERE latitude IS NOT NULL AND longitude IS NOT NULL
        HAVING distance <= :radius
        ORDER BY distance ASC
        LIMIT 1";

        $result = $conn->executeQuery($sql, ['lat' => $lat, 'lng' => $lng, 'radius' => $radiusMeters])->fetchAssociative();

        if (!$result) {
            return null;
        }

        return $this->find($result['id']);
    }
}
