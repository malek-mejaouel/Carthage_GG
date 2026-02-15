<?php

namespace App\Controller;

use App\Entity\News;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * NewsController
 * 
 * Handles all web pages for news article management.
 * Includes operations for creating, reading, updating, and deleting news articles.
 * All endpoints return rendered HTML responses.
 */
class NewsController extends AbstractController
{
    // Injected dependencies for file handling and database operations
    private $slugger;              // Converts filenames to safe slugs
    private $entityManager;        // Doctrine ORM entity manager for database operations

    /**
     * Constructor - Injects dependencies
     * 
     * @param SluggerInterface $slugger For safely converting filenames
     * @param EntityManagerInterface $entityManager For database operations
     */
    public function __construct(SluggerInterface $slugger, EntityManagerInterface $entityManager)
    {
        $this->slugger = $slugger;
        $this->entityManager = $entityManager;
    }

    /**
     * SHOW ADD NEWS FORM
     * 
     * Route: GET /news/add
     * Purpose: Display the form to create a new news article
     * Returns: Rendered HTML form
     * 
     * @return Response Rendered add news form
     */
    #[Route('/news/add', name: 'app_news_add_form', methods: ['GET'])]
    public function showAddNewsForm(): Response
    {
        return $this->render('News/add.html.twig', [
            'titre' => '',
            'categorie' => '',
            'contenu' => '',
        ]);
    }

    /**
     * ADD NEWS ENDPOINT
     * 
     * Route: POST /news/add
     * Purpose: Create a new news article with title, content, category, and optional image
     * Returns: Rendered form with success/error messages
     * 
     * @param Request $request The HTTP request object containing form data
     * @return Response Rendered response with success or validation errors
     */
    #[Route('/news/add', name: 'app_news_add', methods: ['POST'])]
    public function addNews(Request $request): Response
    {
        try {
            // ==================== 1. EXTRACT FORM DATA ====================
            // Trim whitespace from input to prevent empty spaces
            $titre = trim($request->request->get('titre'));
            $contenu = trim($request->request->get('contenu'));
            $categorie = trim($request->request->get('categorie'));
            
            // ==================== 2. VALIDATE INPUT DATA ====================
            // Initialize errors array to collect validation messages
            $errors = [];
            
            // Title validation: required, length between 3-255 characters
            if (empty($titre)) {
                $errors['title'] = 'Title is required';
            } elseif (strlen($titre) < 3) {
                $errors['title'] = 'Title must be at least 3 characters long';
            } elseif (strlen($titre) > 255) {
                $errors['title'] = 'Title must not exceed 255 characters';
            }
            
            // Content validation: required, minimum 10 characters (to prevent spam)
            if (empty($contenu)) {
                $errors['content'] = 'Content is required';
            } elseif (strlen($contenu) < 10) {
                $errors['content'] = 'Content must be at least 10 characters long';
            }
            
            // Category validation: required, max 100 characters
            if (empty($categorie)) {
                $errors['category'] = 'Category is required';
            } elseif (strlen($categorie) > 100) {
                $errors['category'] = 'Category must not exceed 100 characters';
            }

            // Return form with errors if validation failed
            if (!empty($errors)) {
                return $this->render('News/add.html.twig', [
                    'errors' => $errors,
                    'titre' => $titre,
                    'categorie' => $categorie,
                    'contenu' => $contenu,
                ]);
            }

            // ==================== 3. HANDLE IMAGE UPLOAD ====================
            // Set default image if none provided (empty -> template will show fallback)
            $imagePath = '';
            
            // Get the uploaded image file from request
            $imageFile = $request->files->get('image');
            
            if ($imageFile) {
                // Extract filename without extension: e.g., "my-news.jpg" -> "my-news"
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                
                // Convert filename to URL-safe slug: "My News" -> "my-news"
                $safeFilename = $this->slugger->slug($originalFilename);
                
                // Create unique filename by appending uniqid to prevent overwrites
                // Example result: "my-news-61f73c1a2b4c5.jpg"
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    // Move uploaded file to the news uploads directory
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/news',
                        $newFilename
                    );
                    // Store filename (not full path) in database
                    $imagePath = $newFilename;
                } catch (FileException $e) {
                    // Return error if file upload fails
                    return $this->render('News/add.html.twig', [
                        'errors' => ['image' => 'Failed to upload image'],
                        'titre' => $titre,
                        'categorie' => $categorie,
                        'contenu' => $contenu,
                    ]);
                }
            }

            // ==================== 4. CREATE NEWS ENTITY ====================
            // Instantiate new News entity
            $news = new News();
            
            // Set all fields on the news object
            $news->setTitre($titre);
            $news->setContenu($contenu);
            $news->setCategorie($categorie);
            $news->setImage($imagePath);
            
            // Set publication date to current time
            $news->setDatePublication(new \DateTime());

            // ==================== 5. PERSIST TO DATABASE ====================
            // Tell Doctrine to save this entity
            $this->entityManager->persist($news);
            
            // Execute the INSERT SQL query
            $this->entityManager->flush();

            // ==================== 6. RETURN SUCCESS RESPONSE ====================
            return $this->render('News/add.html.twig', [
                'success' => true,
                'successMessage' => 'News article published successfully!',
                'titre' => '',
                'categorie' => '',
                'contenu' => '',
            ]);

        } catch (\Exception $e) {
            // Catch any unexpected errors and return error response
            return $this->render('News/add.html.twig', [
                'errors' => ['general' => 'Error: ' . $e->getMessage()],
                'titre' => $titre ?? '',
                'categorie' => $categorie ?? '',
                'contenu' => $contenu ?? '',
            ]);
        }
    }

    /**
     * LIST ALL NEWS ENDPOINT
     * 
     * Route: GET /news
     * Purpose: Retrieve and display all news articles with search/filter/sort
     * Returns: Rendered list of news objects
     * 
     * @return Response Rendered news list page
     */
    #[Route('/news', name: 'app_news_list', methods: ['GET'])]
    public function listNews(Request $request): Response
    {
        try {
            // Get filter parameters from query string
            $search = $request->query->get('search', '');
            $category = $request->query->get('category', '');
            $sort = $request->query->get('sort', 'newest'); // 'newest' or 'oldest'
            
            // Get the repository to query the database
            $newsRepository = $this->entityManager->getRepository(News::class);
            
            // Fetch all news articles from database
            $newsList = $newsRepository->findAll();
            
            // Filter by search term (title)
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $newsList = array_filter($newsList, function(News $news) use ($searchLower) {
                    return strpos(strtolower($news->getTitre()), $searchLower) !== false;
                });
            }
            
            // Filter by category
            if (!empty($category)) {
                $newsList = array_filter($newsList, function(News $news) use ($category) {
                    return strtolower($news->getCategorie()) === strtolower($category);
                });
            }
            
            // Sort by date
            usort($newsList, function(News $a, News $b) use ($sort) {
                $comparison = $b->getDatePublication() <=> $a->getDatePublication();
                return ($sort === 'oldest') ? -$comparison : $comparison;
            });

            // Return rendered news list page
            return $this->render('News/News.html.twig', [
                'newsList' => $newsList,
                'search' => $search,
                'category' => $category,
                'sort' => $sort,
                'categories' => [
                    'Tournaments' => 'tournaments',
                    'Matches' => 'matches',
                    'Players' => 'players',
                    'Events' => 'events',
                    'Updates' => 'updates',
                    'Announcements' => 'announcements',
                    'Results' => 'results',
                    'Statistics' => 'statistics',
                ]
            ]);

        } catch (\Exception $e) {
            return $this->render('News/News.html.twig', [
                'newsList' => [],
                'error' => 'Error loading news: ' . $e->getMessage(),
                'search' => '',
                'category' => '',
                'sort' => 'newest',
                'categories' => []
            ]);
        }
    }

    /**
     * LIST ALL NEWS API ENDPOINT (FOR AJAX)
     * 
     * Route: GET /api/news/list
     * Purpose: Retrieve all news articles as JSON for AJAX requests
     * Returns: JSON array of all news articles
     * 
     * @return Response JSON response
     */
    #[Route('/api/news/list', name: 'api_news_list', methods: ['GET'])]
    public function apiListNews(): Response
    {
        try {
            // Get the repository to query the database
            $newsRepository = $this->entityManager->getRepository(News::class);
            
            // Fetch all news articles from database
            $newsList = $newsRepository->findAll();

            // Transform news entities into JSON-friendly array format
            $data = [];
            foreach ($newsList as $news) {
                $data[] = [
                    'id' => $news->getNewsId(),
                    'title' => $news->getTitre(),
                    'content' => $news->getContenu(),
                    'image' => $news->getImage(),
                    'category' => $news->getCategorie(),
                    // Format date as human-readable string: "2026-02-06 15:30:00"
                    'date_publication' => $news->getDatePublication()?->format('Y-m-d H:i:s')
                ];
            }

            // Return all news as JSON
            return $this->json($data, Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET SINGLE NEWS ENDPOINT
     * 
     * Route: GET /news/{id}
     * Purpose: Retrieve and display a single news article
     * Used when viewing a news detail page
     * 
     * @param int $id The news article ID
     * @return Response Rendered news detail page or 404 error
     */
    #[Route('/news/{id}', name: 'app_news_detail', methods: ['GET'])]
    public function getNews(int $id): Response
    {
        try {
            // Get the repository and find news by ID
            $newsRepository = $this->entityManager->getRepository(News::class);
            $news = $newsRepository->find($id);

            // Return 404 if news not found
            if (!$news) {
                throw $this->createNotFoundException('News article not found');
            }

            // Render the detail page
            return $this->render('News/Detail.html.twig', [
                'news' => $news,
                'newsId' => $id,
            ]);

        } catch (\Exception $e) {
            throw $this->createNotFoundException('News article not found');
        }
    }

    /**
     * GET SINGLE NEWS API ENDPOINT (FOR AJAX)
     * 
     * Route: GET /api/news/{id}
     * Purpose: Retrieve a single news article as JSON
     * 
     * @param int $id The news article ID
     * @return Response JSON object of the news article or 404 error
     */
    #[Route('/api/news/{id}', name: 'api_news_get', methods: ['GET'])]
    public function apiGetNews(int $id): Response
    {
        try {
            // Get the repository and find news by ID
            $newsRepository = $this->entityManager->getRepository(News::class);
            $news = $newsRepository->find($id);

            // Return 404 if news not found
            if (!$news) {
                return $this->json(['error' => 'News not found'], Response::HTTP_NOT_FOUND);
            }

            // Transform news entity to JSON array
            $data = [
                'id' => $news->getNewsId(),
                'title' => $news->getTitre(),
                'content' => $news->getContenu(),
                'image' => $news->getImage(),
                'category' => $news->getCategorie(),
                'date_publication' => $news->getDatePublication()?->format('Y-m-d H:i:s')
            ];

            return $this->json($data, Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * SHOW EDIT NEWS FORM
     * 
     * Route: GET /news/{id}/edit
     * Purpose: Display the form to edit an existing news article
     * Returns: Rendered edit news form with current data
     * 
     * @param int $id The news article ID
     * @return Response Rendered edit news form
     */
    #[Route('/news/{id}/edit', name: 'app_news_edit_form', methods: ['GET'])]
    public function showEditNewsForm(int $id): Response
    {
        try {
            // Retrieve the news article to edit
            $newsRepository = $this->entityManager->getRepository(News::class);
            $news = $newsRepository->find($id);

            // Return 404 if news doesn't exist
            if (!$news) {
                throw $this->createNotFoundException('News article not found');
            }

            return $this->render('News/edit.html.twig', [
                'news' => $news,
            ]);
        } catch (\Exception $e) {
            throw $this->createNotFoundException('News article not found');
        }
    }

    /**
     * EDIT NEWS ENDPOINT
     * 
     * Route: POST /news/{id}/edit
     * Purpose: Update an existing news article
     * Can update title, content, category, and replace image
     * 
     * @param Request $request The HTTP request with updated form data
     * @param int $id The news article ID to update
     * @return Response Rendered edit form with success or error messages
     */
    #[Route('/news/{id}/edit', name: 'app_news_edit', methods: ['POST'])]
    public function editNews(Request $request, int $id): Response
    {
        try {
            // ==================== 1. FIND NEWS ARTICLE ====================
            // Retrieve the news article to update
            $newsRepository = $this->entityManager->getRepository(News::class);
            $news = $newsRepository->find($id);

            // Return 404 if news doesn't exist
            if (!$news) {
                throw $this->createNotFoundException('News article not found');
            }

            // ==================== 2. EXTRACT UPDATED DATA ====================
            $titre = trim($request->request->get('titre'));
            $contenu = trim($request->request->get('contenu'));
            $categorie = trim($request->request->get('categorie'));

            // ==================== 3. VALIDATE UPDATED DATA ====================
            $errors = [];
            
            if (empty($titre)) {
                $errors['title'] = 'Title is required';
            } elseif (strlen($titre) < 3) {
                $errors['title'] = 'Title must be at least 3 characters long';
            } elseif (strlen($titre) > 255) {
                $errors['title'] = 'Title must not exceed 255 characters';
            }
            
            if (empty($contenu)) {
                $errors['content'] = 'Content is required';
            } elseif (strlen($contenu) < 10) {
                $errors['content'] = 'Content must be at least 10 characters long';
            }
            
            if (empty($categorie)) {
                $errors['category'] = 'Category is required';
            } elseif (strlen($categorie) > 100) {
                $errors['category'] = 'Category must not exceed 100 characters';
            }

            if (!empty($errors)) {
                return $this->render('News/edit.html.twig', [
                    'news' => $news,
                    'errors' => $errors,
                ]);
            }

            // ==================== 4. UPDATE NEWS FIELDS ====================
            // Update the basic fields
            $news->setTitre($titre);
            $news->setContenu($contenu);
            $news->setCategorie($categorie);

            // ==================== 5. HANDLE IMAGE UPDATE (OPTIONAL) ====================
            // If a new image was uploaded, replace the old one
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    // Move new image to uploads folder
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/news',
                        $newFilename
                    );
                    // Update the image field
                    $news->setImage($newFilename);
                } catch (FileException $e) {
                    return $this->render('News/edit.html.twig', [
                        'news' => $news,
                        'errors' => ['image' => 'Failed to upload image'],
                    ]);
                }
            }

            // ==================== 6. SAVE CHANGES ====================
            // Flush all updates to database
            $this->entityManager->flush();

            return $this->render('News/edit.html.twig', [
                'news' => $news,
                'success' => true,
                'successMessage' => 'Article updated successfully!',
            ]);

        } catch (\Exception $e) {
            return $this->render('News/edit.html.twig', [
                'news' => $news ?? null,
                'errors' => ['general' => 'Error: ' . $e->getMessage()],
            ]);
        }
    }

    /**
     * DELETE NEWS ENDPOINT
     * 
     * Route: GET /news/{id}/delete or POST /news/{id}/delete
     * Purpose: Delete a news article (with confirmation)
     * Returns: Redirect to news list after deletion
     * 
     * @param int $id The news article ID to delete
     * @return Response Redirect to news list
     */
    #[Route('/news/{id}/delete', name: 'app_news_delete', methods: ['GET', 'POST'])]
    public function deleteNews(int $id): Response
    {
        try {
            // ==================== 1. FIND NEWS TO DELETE ====================
            $newsRepository = $this->entityManager->getRepository(News::class);
            $news = $newsRepository->find($id);

            // Return 404 if news doesn't exist
            if (!$news) {
                throw $this->createNotFoundException('News article not found');
            }

            // ==================== 2. DELETE FROM DATABASE ====================
            // Mark entity for deletion
            $this->entityManager->remove($news);
            
            // Execute the DELETE SQL query
            // NOTE: Related comments will cascade delete due to database constraints
            $this->entityManager->flush();

            // ==================== 3. REDIRECT TO NEWS LIST ====================
            $this->addFlash('success', 'News article deleted successfully');
            return $this->redirectToRoute('app_news_list');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting news: ' . $e->getMessage());
            return $this->redirectToRoute('app_news_list');
        }
    }

    /**
     * DELETE NEWS API ENDPOINT (FOR AJAX)
     * 
     * Route: DELETE /api/news/delete/{id} or POST /api/news/delete/{id}
     * Purpose: Permanently delete a news article via API
     * Returns: JSON response
     * 
     * @param int $id The news article ID to delete
     * @return Response JSON response confirming deletion
     */
    #[Route('/api/news/delete/{id}', name: 'api_news_delete', methods: ['DELETE', 'POST'])]
    public function apiDeleteNews(int $id): Response
    {
        try {
            // ==================== 1. FIND NEWS TO DELETE ====================
            $newsRepository = $this->entityManager->getRepository(News::class);
            $news = $newsRepository->find($id);

            // Return 404 if news doesn't exist
            if (!$news) {
                return $this->json(['message' => 'News not found'], Response::HTTP_NOT_FOUND);
            }

            // ==================== 2. DELETE FROM DATABASE ====================
            // Mark entity for deletion
            $this->entityManager->remove($news);
            
            // Execute the DELETE SQL query
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'News deleted successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}