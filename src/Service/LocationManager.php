<?php

namespace App\Service;

use App\Entity\Location;

class LocationManager
{
    public function validate(Location $location): bool
    {
        $name = $location->getName();
        if (empty($name)) {
            throw new \InvalidArgumentException('Name is required');
        }
        if (strlen($name) < 3) {
            throw new \InvalidArgumentException('Name must be at least 3 characters');
        }
        $address = $location->getAddress();
        if ($address !== null && strlen($address) > 500) {
            throw new \InvalidArgumentException('Address must not exceed 500 characters');
        }
        $capacity = $location->getCapacity();
        if ($capacity !== null && $capacity < 0) {
            throw new \InvalidArgumentException('Capacity must be zero or positive');
        }
        return true;
    }
}
