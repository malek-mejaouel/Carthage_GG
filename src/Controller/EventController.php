<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/event')]
class EventController extends AbstractController
{
    #[Route('/', name: 'event_index')]
    public function index(EntityManagerInterface $em): Response
    {
        return $this->render('event/index.html.twig', [
            'events' => $em->getRepository(Event::class)->findAll()
        ]);
    }

    #[Route('/view/{id}', name: 'event_show')]
    public function show(?Event $event): Response
    {
        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/new', name: 'event_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $date = $request->request->get('date') ?? $request->request->get('start_at');
            $endDate = $request->request->get('end_at');
            $locationId = $request->request->get('location');

            $lat = $request->request->get('location_latitude');
            $lng = $request->request->get('location_longitude');
            $address = $request->request->get('location_address');
            $placeId = $request->request->get('location_place_id');

            $location = null;
            if ($locationId) {
                $location = $em->getRepository(Location::class)->find($locationId);
            } elseif ($lat && $lng) {
                // try to find a nearby existing location to avoid duplicates
                $repo = $em->getRepository(Location::class);
                if (method_exists($repo, 'findNearby')) {
                    $existing = $repo->findNearby((float)$lat, (float)$lng, 50);
                } else {
                    $existing = null;
                }

                if ($existing) {
                    $location = $existing;
                } else {
                    // create a new Location from provided lat/lng/address
                    $location = new Location();
                    $location->setName($address ?: 'Selected location');
                    $location->setAddress($address ?: null);
                    if (method_exists($location, 'setLatitude')) {
                        $location->setLatitude((float)$lat);
                    }
                    if (method_exists($location, 'setLongitude')) {
                        $location->setLongitude((float)$lng);
                    }
                    if ($placeId && method_exists($location, 'setPlaceId')) {
                        $location->setPlaceId($placeId);
                    }
                    $em->persist($location);
                    $em->flush();
                }
            }

            if (!$title || !$date || !$location) {
                $this->addFlash('error', 'Données invalides');
            } else {
                $event = new Event();
                $event->setTitle($title);
                try {
                    $event->setStartAt(new \DateTimeImmutable($date)
);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Date invalide');
                    return $this->redirectToRoute('event_new');
                }

                if ($endDate) {
                    try {
                        $event->setEndAt(new \DateTimeImmutable($endDate));
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Date de fin invalide');
                        return $this->redirectToRoute('event_new');
                    }
                }
                $event->setLocation($location);

                // Capacity checks: prevent creation if current reservations exceed location capacity
                $locCapacity = $location->getCapacity();
                if ($locCapacity !== null) {
                    $reserved = $event->getReservedSeats();
                    if ($reserved > $locCapacity) {
                        $this->addFlash('error', sprintf('Le nombre actuel de réservations (%d) dépasse la capacité du lieu (%d).', $reserved, $locCapacity));
                        return $this->redirectToRoute('event_new');
                    }
                    if ($event->getMaxSeats() > $locCapacity) {
                        $this->addFlash('error', sprintf('Le nombre de places max (%d) dépasse la capacité du lieu (%d).', $event->getMaxSeats(), $locCapacity));
                        return $this->redirectToRoute('event_new');
                    }
                }

                $em->persist($event);
                $em->flush();

                return $this->redirectToRoute('event_index');
            }
        }

        return $this->render('event/new.html.twig', [
            'locations' => $em->getRepository(Location::class)->findAll(),
            'googleMapsKey' => $_ENV['GOOGLE_MAPS_API_KEY'] ?? null,
        ]);
    }

    #[Route('/delete/{id}', name: 'event_delete')]
    public function delete(?Event $event, EntityManagerInterface $em): Response
    {
        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        $em->remove($event);
        $em->flush();

        return $this->redirectToRoute('event_index');
    }

    #[Route('/edit/{id}', name: 'event_edit')]
    public function edit(Event $event = null, Request $request, EntityManagerInterface $em): Response
    {
        if (!$event) {
            return $this->redirectToRoute('event_index');
        }

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $date = $request->request->get('date') ?? $request->request->get('start_at');
            $endDate = $request->request->get('end_at');
            $locationId = $request->request->get('location');

            $location = $em->getRepository(Location::class)->find($locationId);

            if (!$title || !$date || !$location) {
                $this->addFlash('error', 'Données invalides');
            } else {
                $event->setTitle($title);
                try {
                    $event->setStartAt(new \DateTimeImmutable($date));
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Date invalide');
                    return $this->redirectToRoute('event_edit', ['id' => $event->getId()]);
                }

                if ($endDate) {
                    try {
                        $event->setEndAt(new \DateTimeImmutable($endDate));
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Date de fin invalide');
                        return $this->redirectToRoute('event_edit', ['id' => $event->getId()]);
                    }
                } else {
                    $event->setEndAt(null);
                }

                $event->setLocation($location);

                // Capacity checks on edit: ensure existing reservations do not exceed location capacity
                $locCapacity = $location->getCapacity();
                if ($locCapacity !== null) {
                    $reserved = $event->getReservedSeats();
                    if ($reserved > $locCapacity) {
                        $this->addFlash('error', sprintf('Le nombre actuel de réservations (%d) dépasse la capacité du lieu (%d).', $reserved, $locCapacity));
                        return $this->redirectToRoute('event_edit', ['id' => $event->getId()]);
                    }
                    if ($event->getMaxSeats() > $locCapacity) {
                        $this->addFlash('error', sprintf('Le nombre de places max (%d) dépasse la capacité du lieu (%d).', $event->getMaxSeats(), $locCapacity));
                        return $this->redirectToRoute('event_edit', ['id' => $event->getId()]);
                    }
                }

                $em->flush();
                return $this->redirectToRoute('event_index');
            }
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'locations' => $em->getRepository(Location::class)->findAll(),
        ]);
    }

    #[Route('/events', name: 'events_redirect', methods: ['GET'])]
    public function eventsRedirect(): Response
    {
        return $this->redirectToRoute('event_index');
    }
}
