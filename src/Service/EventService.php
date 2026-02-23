<?php

namespace App\Service;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Entity\Location;
use Doctrine\Persistence\ManagerRegistry;

class EventService
{
    public function __construct(private EventRepository $eventRepository)
    {
    }

    public function validateEvent(Event $event): void
    {
        if ($event->getEndAt() && $event->getStartAt() > $event->getEndAt()) {
            throw new \Exception("La date de fin doit être après la date de début.");
        }
    }

    public function isValidDate(?\DateTimeImmutable $start): bool
    {
        if (!$start) return false;
        $now = new \DateTimeImmutable();
        return $start > $now;
    }

    public function isLocationAvailable(?Location $location, ?\DateTimeImmutable $start, ?\DateTimeImmutable $end = null): bool
    {
        if (!$location || !$start) return false;

        $conflicts = $this->eventRepository->countConflicting($location, $start, $end);
        return $conflicts === 0;
    }
}