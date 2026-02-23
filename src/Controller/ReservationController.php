<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'reservation_index')]
    public function index(EntityManagerInterface $em): Response
    {
        return $this->render('reservation/index.html.twig', [
            'reservations' => $em->getRepository(Reservation::class)->findAll()
        ]);
    }

    #[Route('/new', name: 'reservation_new')]
    public function new(Request $request, EntityManagerInterface $em, \App\Service\ReservationService $reservationService): Response
    {
        if ($request->isMethod('POST')) {
            $fullName = $request->request->get('fullName') ?? $request->request->get('full_name') ?? $request->request->get('username');
            $eventId = $request->request->get('event');
            $seats = (int)($request->request->get('seats') ?? 1);

            $event = $em->getRepository(Event::class)->find($eventId);

            if (!$fullName || !$event || $seats <= 0) {
                $this->addFlash('error', 'Données invalides');
            } else {
                $reservation = new Reservation();
                $reservation->setFullName($fullName);
                $reservation->setSeats($seats);
                $reservation->setEvent($event);

                // Determine status (confirmed or waiting)
                $reservationService->handleReservation($reservation);

                $em->persist($reservation);
                $em->flush();

                return $this->redirectToRoute('reservation_index');
            }
        }

        return $this->render('reservation/new.html.twig', [
            'events' => $em->getRepository(Event::class)->findAll()
        ]);
    }

    #[Route('/delete/{id}', name: 'reservation_delete')]
    public function delete(Reservation $reservation, EntityManagerInterface $em, \App\Service\ReservationService $reservationService): Response
    {
        $event = $reservation->getEvent();

        $em->remove($reservation);
        $em->flush();

        // After deletion, try to promote waitlist for this event
        if ($event) {
            $reservationService->promoteWaitlist($event);
        }

        return $this->redirectToRoute('reservation_index');
    }

    #[Route('/edit/{id}', name: 'reservation_edit')]
    public function edit(Reservation $reservation = null, Request $request, EntityManagerInterface $em, \App\Service\ReservationService $reservationService): Response
    {
        if (!$reservation) {
            return $this->redirectToRoute('reservation_index');
        }

        if ($request->isMethod('POST')) {
            $fullName = $request->request->get('fullName') ?? $request->request->get('full_name') ?? $request->request->get('username');
            $eventId = $request->request->get('event');
            $seats = (int)($request->request->get('seats') ?? 1);

            $event = $em->getRepository(Event::class)->find($eventId);

            if (!$fullName || !$event || $seats <= 0) {
                $this->addFlash('error', 'Données invalides');
            } else {
                $reservation->setFullName($fullName);
                $reservation->setSeats($seats);
                $reservation->setEvent($event);

                // Re-evaluate status (e.g., seats changed)
                $reservationService->handleReservation($reservation);

                $em->flush();

                return $this->redirectToRoute('reservation_index');
            }
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'events' => $em->getRepository(Event::class)->findAll(),
        ]);
    }
}
