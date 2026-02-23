<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/store/categories')]
class CategoryAdminController extends AbstractController
{
    #[Route('', name: 'admin_categories_index')]
    public function index(CategoryRepository $repo): Response
    {
        return $this->render('admin/store/category/index.html.twig', [
            'categories' => $repo->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'admin_categories_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            return $this->redirectToRoute('admin_categories_index');
        }
        return $this->render('admin/store/category/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_categories_edit')]
    public function edit(Category $category, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('admin_categories_index');
        }
        return $this->render('admin/store/category/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_categories_delete', methods: ['POST'])]
    public function delete(Category $category, EntityManagerInterface $em): Response
    {
        $em->remove($category);
        $em->flush();
        return $this->redirectToRoute('admin_categories_index');
    }
}
