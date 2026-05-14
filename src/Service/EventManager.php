<?php

namespace App\Service;

use App\Entity\Event;

class EventManager
{
    public function validate(Event $event): bool
    {
        $title = $event->getTitle();
        if (empty($title)) {
            throw new \InvalidArgumentException('Title is required');
        }
        if (strlen($title) < 3) {
            throw new \InvalidArgumentException('Title must be at least 3 characters');
        }
        try {
            $start = $event->getStartAt();
        } catch (\Error $e) {
            $start = null;
        }
        if ($start === null) {
            throw new \InvalidArgumentException('Start date is required');
        }
        $location = $event->getLocation();
        if ($location === null) {
            throw new \InvalidArgumentException('Location is required');
        }
        $maxSeats = $event->getMaxSeats();
        if ($maxSeats < 1) {
            throw new \InvalidArgumentException('Max seats must be at least 1');
        }
        return true;
    }
}
