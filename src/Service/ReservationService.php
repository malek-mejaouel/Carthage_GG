<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;

class ReservationService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function handleReservation(Reservation $reservation): void
    {
        $event = $reservation->getEvent();

        if (!$event) {
            throw new \Exception("Event obligatoire.");
        }

        $availableSeats = $event->getAvailableSeats();

        // If no seats available, place automatically on WAITING list
        if ($availableSeats <= 0) {
            $reservation->setStatus(Reservation::STATUS_WAITING);
            return;
        }

        // If requested seats exceed available, put on waiting list
        if ($reservation->getSeats() > $availableSeats) {
            $reservation->setStatus(Reservation::STATUS_WAITING);
        } else {
            $reservation->setStatus(Reservation::STATUS_CONFIRMED);
        }
    }

    /**
     * Promote waiting reservations when seats free up (e.g., after a cancellation).
     */
    public function promoteWaitlist(Event $event): void
    {
        $available = $event->getAvailableSeats();
        if ($available <= 0) {
            return;
        }

        $repo = $this->em->getRepository(Reservation::class);
        $waiting = $repo->findBy([
            'event' => $event,
            'status' => Reservation::STATUS_WAITING,
        ], ['createdAt' => 'ASC']);

        foreach ($waiting as $res) {
            if ($res->getSeats() <= $available) {
                $res->setStatus(Reservation::STATUS_CONFIRMED);
                $this->em->persist($res);
                $available -= $res->getSeats();
            }
            if ($available <= 0) {
                break;
            }
        }

        if (!empty($waiting)) {
            $this->em->flush();
        }
    }
}