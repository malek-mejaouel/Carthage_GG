<?php

namespace App\Controller;

use App\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/location')]
class LocationController extends AbstractController
{
    #[Route('/', name: 'location_index')]
    public function index(EntityManagerInterface $em): Response
    {
        return $this->render('location/index.html.twig', [
            'locations' => $em->getRepository(Location::class)->findAll()
        ]);
    }

    #[Route('/new', name: 'location_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim((string)$request->request->get('name'));
            $addressRaw = $request->request->get('address');
            $address = is_string($addressRaw) ? trim($addressRaw) : null;
            $capacity = (int)$request->request->get('capacity');

            if ($name === '' || $capacity <= 0) {
                $this->addFlash('error', 'Champs invalides');
            } else {
                $location = new Location();
                $location->setName($name);
                $location->setAddress($address);
                $location->setCapacity($capacity);

                $em->persist($location);
                $em->flush();

                return $this->redirectToRoute('location_index');
            }
        }

        return $this->render('location/new.html.twig');
    }

    #[Route('/delete/{id}', name: 'location_delete')]
    public function delete(Location $location, EntityManagerInterface $em): Response
    {
        $em->remove($location);
        $em->flush();

        return $this->redirectToRoute('location_index');
    }

    #[Route('/edit/{id}', name: 'location_edit')]
    public function edit(Location $location = null, Request $request, EntityManagerInterface $em): Response
    {
        if (!$location) {
            return $this->redirectToRoute('location_index');
        }

        if ($request->isMethod('POST')) {
            $name = trim((string)$request->request->get('name'));
            $addressRaw = $request->request->get('address');
            $address = is_string($addressRaw) ? trim($addressRaw) : null;
            $capacity = (int)$request->request->get('capacity');

            if ($name === '' || $capacity <= 0) {
                $this->addFlash('error', 'Champs invalides');
            } else {
                $location->setName($name);
                $location->setAddress($address);
                $location->setCapacity($capacity);

                $em->flush();
                return $this->redirectToRoute('location_index');
            }
        }

        return $this->render('location/edit.html.twig', [
            'location' => $location,
        ]);
    }

    #[Route('/view/{id}', name: 'location_detail', methods: ['GET'])]
    public function detail(Location $location = null): Response
    {
        if (!$location) {
            return $this->redirectToRoute('location_index');
        }
        return $this->render('location/show.html.twig', [
            'location' => $location,
        ]);
    }

    #[Route('/{id}/book', name: 'location_book', methods: ['GET', 'POST'])]
    public function book(Location $location = null, Request $request): Response
    {
        if (!$location) {
            return $this->redirectToRoute('location_index');
        }
        // Simple booking stub (extend with real booking logic if needed)
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Booking request submitted');
            return $this->redirectToRoute('location_detail', ['id' => $location->getId()]);
        }
        return $this->render('location/book.html.twig', [
            'location' => $location,
        ]);
    }
}
