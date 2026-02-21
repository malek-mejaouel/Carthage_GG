<?php

namespace App\Controller;

use App\Entity\News;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/admin/dashboard/admin', name: 'app_admin_dash_admin')]
    public function adminSection(): Response
    {
        return $this->render('admin/section-admin.html.twig');
    }

    #[Route('/admin/dashboard/game', name: 'app_admin_dash_game')]
    public function gameSection(): Response
    {
        return $this->render('admin/section-game.html.twig');
    }

    #[Route('/admin/dashboard/matches', name: 'app_admin_dash_matches')]
    public function matchesSection(): Response
    {
        return $this->render('admin/section-matches.html.twig');
    }

    #[Route('/admin/dashboard/tournament', name: 'app_admin_dash_tournament')]
    public function tournamentSection(): Response
    {
        return $this->render('admin/section-tournament.html.twig');
    }

    #[Route('/admin/dashboard/news', name: 'app_admin_dash_news')]
    public function newsSection(NewsRepository $newsRepository): Response
    {
        $news = $newsRepository->findAllOrderedByDate();
        return $this->render('admin/section-news.html.twig', [
            'news' => $news
        ]);
    }

    /**
     * Display form for adding new news (admin)
     */
    #[Route('/admin/news/new', name: 'app_admin_news_new', methods: ['POST'])]
    public function addNewsAdmin(
        Request $request, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        try {
            // Symfony DOES parse bracket notation automatically!
            // Check if news_type array exists
            if ($request->request->has('news_type')) {
                $newsTypeData = $request->request->all()['news_type'] ?? [];
            } else {
                // If not, check for flattened keys
                $allData = $request->request->all();
                $newsTypeData = [];
                foreach ($allData as $key => $value) {
                    if (is_string($key) && strpos($key, 'news_type[') === 0) {
                        $fieldName = str_replace(['news_type[', ']'], '', $key);
                        $newsTypeData[$fieldName] = $value;
                    }
                }
            }
            
            // Ensure it's an array
            if (!is_array($newsTypeData)) {
                $newsTypeData = [];
            }
            
            $titre = trim($newsTypeData['titre'] ?? '');
            $contenu = trim($newsTypeData['contenu'] ?? '');
            $categorie = trim($newsTypeData['categorie'] ?? '');
            
            // Validation
            $errors = [];
            
            if (empty($titre)) {
                $errors['title'] = 'Title is required';
            } elseif (strlen($titre) < 3 || strlen($titre) > 255) {
                $errors['title'] = 'Title must be between 3 and 255 characters';
            }
            
            if (empty($contenu)) {
                $errors['content'] = 'Content is required';
            } elseif (strlen($contenu) < 10) {
                $errors['content'] = 'Content must be at least 10 characters long';
            }
            
            if (empty($categorie) || strlen($categorie) > 100) {
                $errors['category'] = 'Category is required and must not exceed 100 characters';
            }

            // Handle errors
            if (!empty($errors)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Please fix the errors in the form: ' . implode(', ', $errors),
                    'errors' => $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            // Handle image upload
            // Use empty string as default so templates will display a fallback image
            $imagePath = '';
            
            // Get files - also check both nested and flattened structures
            $imageFile = null;
            
            // First check if files are nested under news_type
            if ($request->files->has('news_type')) {
                $newsTypeFiles = $request->files->all()['news_type'] ?? null;
                if (is_array($newsTypeFiles) && isset($newsTypeFiles['image'])) {
                    $imageFile = $newsTypeFiles['image'];
                }
            }
            
            // If not found, check flattened structure
            if (!$imageFile) {
                $allFiles = $request->files->all();
                foreach ($allFiles as $key => $file) {
                    if (is_string($key) && strpos($key, 'news_type[image]') === 0 && $file && $file->isValid()) {
                        $imageFile = $file;
                        break;
                    }
                }
            }
            
            if ($imageFile && $imageFile->isValid()) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/news',
                        $newFilename
                    );
                    $imagePath = $newFilename;
                } catch (FileException $e) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Failed to upload image'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Create and save news
            $news = new News();
            $news->setTitre($titre)
                ->setContenu($contenu)
                ->setCategorie($categorie)
                ->setImage($imagePath)
                ->setDatePublication(new \DateTime());

            $entityManager->persist($news);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'News article created successfully!',
                'news_id' => $news->getNewsId()
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error creating news: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Edit news article (admin)
     */
    #[Route('/admin/news/{newsId}/edit', name: 'app_admin_news_edit', methods: ['GET', 'POST'])]
    public function editNewsAdmin(
        int $newsId, 
        Request $request,
        EntityManagerInterface $entityManager,
        NewsRepository $newsRepository,
        SluggerInterface $slugger
    ): Response {
        $news = $newsRepository->find($newsId);
        
        if (!$news) {
            $this->addFlash('error', 'News article not found');
            return $this->redirectToRoute('app_admin_dash_news');
        }

        if ($request->isMethod('POST')) {
            try {
                $titre = trim($request->request->get('titre'));
                $contenu = trim($request->request->get('contenu'));
                $categorie = trim($request->request->get('categorie'));
                
                $errors = [];
                if (empty($titre) || strlen($titre) < 3 || strlen($titre) > 255) {
                    $errors['title'] = 'Invalid title';
                }
                if (empty($contenu) || strlen($contenu) < 10) {
                    $errors['content'] = 'Invalid content';
                }
                if (empty($categorie) || strlen($categorie) > 100) {
                    $errors['category'] = 'Invalid category';
                }

                if (!empty($errors)) {
                    $this->addFlash('error', 'Please fix the errors');
                    return $this->redirectToRoute('app_admin_dash_news');
                }

                $news->setTitre($titre)
                    ->setContenu($contenu)
                    ->setCategorie($categorie);

                // Handle new image if uploaded
                $imageFile = $request->files->get('image');
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/news',
                            $newFilename
                        );
                        $news->setImage($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload image');
                        return $this->redirectToRoute('app_admin_dash_news');
                    }
                }

                $entityManager->flush();
                $this->addFlash('success', 'News article updated successfully!');
                return $this->redirectToRoute('app_admin_dash_news');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating news: ' . $e->getMessage());
                return $this->redirectToRoute('app_admin_dash_news');
            }
        }

        return $this->render('admin/news/edit.html.twig', [
            'news' => $news
        ]);
    }

    /**
     * Delete news article (admin)
     */
    #[Route('/admin/news/{newsId}/delete', name: 'app_admin_news_delete', methods: ['POST'])]
    public function deleteNewsAdmin(
        int $newsId,
        Request $request,
        EntityManagerInterface $entityManager,
        NewsRepository $newsRepository
    ): Response {
        $news = $newsRepository->find($newsId);
        
        if (!$news) {
            $this->addFlash('error', 'News article not found');
            return $this->redirectToRoute('app_admin_dash_news');
        }

        try {
            // Verify CSRF token
            if (!$this->isCsrfTokenValid('delete' . $newsId, $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid request token');
                return $this->redirectToRoute('app_admin_dash_news');
            }

            $entityManager->remove($news);
            $entityManager->flush();

            $this->addFlash('success', 'News article deleted successfully!');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting news: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_dash_news');
    }
}
