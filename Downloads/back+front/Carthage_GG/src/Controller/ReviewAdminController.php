<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/store/reviews')]
class ReviewAdminController extends AbstractController
{
    #[Route('', name: 'admin_reviews_index')]
    public function index(ReviewRepository $repo): Response
    {
        return $this->render('admin/store/review/index.html.twig', [
            'reviews' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_reviews_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($review);
            $em->flush();
            return $this->redirectToRoute('admin_reviews_index');
        }
        return $this->render('admin/store/review/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_reviews_edit')]
    public function edit(Review $review, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('admin_reviews_index');
        }
        return $this->render('admin/store/review/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_reviews_delete', methods: ['POST'])]
    public function delete(Review $review, EntityManagerInterface $em): Response
    {
        $em->remove($review);
        $em->flush();
        return $this->redirectToRoute('admin_reviews_index');
    }
}
