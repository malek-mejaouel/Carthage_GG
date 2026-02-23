<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin/store/products')]
class ProductAdminController extends AbstractController
{
    #[Route('', name: 'admin_products_index')]
    public function index(ProductRepository $repo): Response
    {
        return $this->render('admin/store/product/index.html.twig', [
            'products' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_products_new')]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
          
            $product->setUpdatedAt(new \DateTime());
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename)->lower();
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/products';
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                try {
                    $imageFile->move($targetDir, $newFilename);
                    $product->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image');
                }
            }
            try {
                $em->persist($product);
                $em->flush();
                $this->addFlash('success', 'Product created successfully.');
                return $this->redirectToRoute('admin_products_index');
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Failed to create product: ' . $e->getMessage());
            }
        }
        return $this->render('admin/store/product/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_products_edit')]
    public function edit(Product $product, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$product->getSlug()) {
                $product->setSlug(strtolower($slugger->slug($product->getName())->toString()));
            }
            $product->setUpdatedAt(new \DateTime());
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename)->lower();
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/products';
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                try {
                    $imageFile->move($targetDir, $newFilename);
                    $product->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image');
                }
            }
            $em->flush();
            return $this->redirectToRoute('admin_products_index');
        }
        return $this->render('admin/store/product/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_products_delete', methods: ['POST'])]
    public function delete(Product $product, EntityManagerInterface $em): Response
    {
        $em->remove($product);
        $em->flush();
        return $this->redirectToRoute('admin_products_index');
    }
}
