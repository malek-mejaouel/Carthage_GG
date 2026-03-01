<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\News;
use App\Entity\User;
use App\Repository\CommentaireRepository;
use App\Service\ProfanityFilter;
use App\Service\GiphyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * CommentaireController
 * 
 * Handles all web endpoints for comment management on news articles.
 * Features include:
 * - Creating, reading, updating, deleting comments
 * - Nested/threaded replies via parent_id encoding
 * - Upvote/downvote system with undo capability
 * - GIF media support in comments
 * 
 * NOTE: Parent ID encoding strategy:
 * Instead of adding a new database column, replies store their parent_id in the content field:
 * Format: "__PARENT__{parent_id}||{actual_comment_text}"
 * This keeps the schema simple while allowing nested comments without migration costs
 */
class CommentaireController extends AbstractController
{
    // Database access for all operations
    private EntityManagerInterface $entityManager;
    private ProfanityFilter $profanityFilter;
    private HttpClientInterface $httpClient;
    private string $recaptchaSecretKey;

    /**
     * Constructor - Injects the Entity Manager and ProfanityFilter
     * 
     * @param EntityManagerInterface $entityManager For database operations
     * @param ProfanityFilter $profanityFilter For filtering profanities in comments
     */
    public function __construct(EntityManagerInterface $entityManager, ProfanityFilter $profanityFilter, HttpClientInterface $httpClient, string $recaptchaSecretKey)
    {
        $this->entityManager = $entityManager;
        $this->profanityFilter = $profanityFilter;
        $this->httpClient = $httpClient;
        $this->recaptchaSecretKey = $recaptchaSecretKey;
    }

    /**
     * GET ALL COMMENTS PAGE
     * 
     * Route: GET /comments/all
     * Purpose: Display ALL comments from all news articles
     * Used for: Admin dashboard to review all comments
     * Returns: Rendered HTML page
     * 
     * @return Response Rendered page with all comments
     */
    #[Route('/comments/all', name: 'app_comments_all', methods: ['GET'])]
    public function all(): Response
    {
        try {
            // Fetch all comments sorted by date (newest first)
            $comments = $this->entityManager->getRepository(Commentaire::class)
                ->findBy([], ['date_commentaire' => 'DESC']);

            return $this->render('Commentaire/all.html.twig', [
                'comments' => $comments,
            ]);
        } catch (\Exception $e) {
            return $this->render('Commentaire/all.html.twig', [
                'comments' => [],
                'error' => 'Error fetching comments: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * GET ALL COMMENTS API ENDPOINT (FOR AJAX)
     * 
     * Route: GET /api/comments/all
     * Purpose: Fetch ALL comments as JSON for AJAX requests
     * Returns: JSON array of comment objects
     * 
     * @return Response JSON response
     */
    #[Route('/api/comments/all', name: 'api_comments_all', methods: ['GET'])]
    public function apiAll(): Response
    {
        try {
            // Fetch all comments sorted by date (newest first)
            $comments = $this->entityManager->getRepository(Commentaire::class)
                ->findBy([], ['date_commentaire' => 'DESC']);

            $data = array_map(function(Commentaire $comment) {
                $user = $comment->getUser();
                $avatarUrl = null;
                if ($user && $user->getAvatar()) {
                    $raw = $user->getAvatar();
                    if (preg_match('#^https?://#i', $raw)) {
                        $avatarUrl = $raw;
                    } else {
                        $path = str_starts_with($raw, '/') ? $raw : '/' . $raw;
                        $pdParam = $this->getParameter('kernel.project_dir');
                        $projectDir = is_string($pdParam) ? $pdParam : getcwd();
                        $abs = $projectDir . '/public' . $path;
                        $avatarUrl = is_file($abs) ? $path : null;
                    }
                }
                return [
                    'commentaire_id' => $comment->getCommentaireId(),
                    'contenu' => $comment->getContenu(),
                    'date_commentaire' => $comment->getDateCommentaire()?->format('Y-m-d H:i:s'),
                    'gif_url' => $comment->getGifUrl(),
                    'upvotes' => $comment->getUpvotes(),
                    'downvotes' => $comment->getDownvotes(),
                    'news_id' => $comment->getNews()->getNewsId(),
                    'news_title' => $comment->getNews()->getTitre(),
                    'user' => $user ? [
                        'id' => $user->getId(),
                        'username' => $user->getUsername(),
                        'avatar' => $avatarUrl,
                        'profile_url' => $this->generateUrl('app_profile_edit', ['id' => $user->getId()]),
                    ] : null,
                ];
            }, $comments);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error fetching comments'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET COMMENTS FOR A NEWS ARTICLE PAGE
     * 
     * Route: GET /comments/news/{news_id}
     * Purpose: Display all comments for a specific news article
     * Returns: Rendered HTML page with comments
     * 
     * @param int $news_id The ID of the news article
     * @return Response Rendered comments page
     */
    #[Route('/comments/news/{news_id}', name: 'app_comments_list', methods: ['GET'])]
    public function list(int $news_id): Response
    {
        try {
            // Verify news exists
            $news = $this->entityManager->getRepository(News::class)->find($news_id);
            if (!$news) {
                throw $this->createNotFoundException('News article not found');
            }

            // Get all comments for this news
            $comments = $this->entityManager->getRepository(Commentaire::class)
                ->findBy(['news' => $news], ['date_commentaire' => 'DESC']);

            return $this->render('Commentaire/list.html.twig', [
                'news' => $news,
                'comments' => $comments,
            ]);
        } catch (\Exception $e) {
            throw $this->createNotFoundException('News article not found');
        }
    }

    /**
     * GET COMMENTS FOR A NEWS ARTICLE API ENDPOINT (FOR AJAX)
     * 
     * Route: GET /api/comments/news/{news_id}
     * Purpose: Fetch all comments as JSON for AJAX requests
     * Returns: JSON array of comments with parent_id extracted
     * 
     * @param int $news_id The ID of the news article to get comments for
     * @return Response JSON response
     */
    #[Route('/api/comments/news/{news_id}', name: 'api_comments_news', methods: ['GET'])]
    public function apiList(int $news_id): Response
    {
        try {
            // Verify news exists
            $news = $this->entityManager->getRepository(News::class)->find($news_id);
            if (!$news) {
                return $this->json([], Response::HTTP_NOT_FOUND);
            }

            // Get all comments for this news
            $comments = $this->entityManager->getRepository(Commentaire::class)
                ->findBy(['news' => $news], ['date_commentaire' => 'DESC']);

            $data = array_map(function(Commentaire $comment) {
                $raw = (string) $comment->getContenu();
                $parentId = null;
                
                if (str_starts_with($raw, '__PARENT__')) {
                    $parts = explode('||', $raw, 2);
                    if (count($parts) === 2) {
                        $parentPart = substr($parts[0], strlen('__PARENT__'));
                        $parentId = is_numeric($parentPart) ? (int)$parentPart : null;
                        $raw = $parts[1];
                    }
                }

                $user = $comment->getUser();
                $avatarUrl = null;
                if ($user && $user->getAvatar()) {
                    $rawAvatar = $user->getAvatar();
                    if (preg_match('#^https?://#i', $rawAvatar)) {
                        $avatarUrl = $rawAvatar;
                    } else {
                        $path = str_starts_with($rawAvatar, '/') ? $rawAvatar : '/' . $rawAvatar;
                        $pdParam = $this->getParameter('kernel.project_dir');
                        $projectDir = is_string($pdParam) ? $pdParam : getcwd();
                        $abs = $projectDir . '/public' . $path;
                        $avatarUrl = is_file($abs) ? $path : null;
                    }
                }

                return [
                    'commentaire_id' => $comment->getCommentaireId(),
                    'contenu' => $raw,
                    'date_commentaire' => $comment->getDateCommentaire()?->format('Y-m-d H:i:s'),
                    'gif_url' => $comment->getGifUrl(),
                    'upvotes' => $comment->getUpvotes(),
                    'downvotes' => $comment->getDownvotes(),
                    'news_id' => $comment->getNews()->getNewsId(),
                    'parent_id' => $parentId,
                    'user' => $user ? [
                        'id' => $user->getId(),
                        'username' => $user->getUsername(),
                        'avatar' => $avatarUrl,
                        'profile_url' => $this->generateUrl('app_profile_edit', ['id' => $user->getId()]),
                    ] : null,
                ];
            }, $comments);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error fetching comments'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * ADD COMMENT API ENDPOINT (FOR AJAX)
     * 
     * Route: POST /api/comments/add
     * Purpose: Create a new comment or reply on a news article
     * Supports: Nested replies, GIF attachments, emoji in comments
     * Returns: JSON response
     * 
     * @param Request $request The HTTP request with JSON body
     * @return Response JSON response
     */
    #[Route('/api/comments/add', name: 'api_comments_add', methods: ['POST'])]
    public function apiAdd(Request $request): Response
    {
        try {
            // Decode JSON request
            $data = json_decode($request->getContent(), true);

            // Validate required fields
            if (!isset($data['news_id']) || empty($data['contenu'])) {
                return $this->json(
                    ['error' => 'Missing required fields: news_id, contenu'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (empty($data['recaptcha_token'])) {
                return $this->json(['error' => 'Missing reCAPTCHA token'], Response::HTTP_BAD_REQUEST);
            }

            $verifyResponse = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $this->recaptchaSecretKey,
                    'response' => $data['recaptcha_token'],
                    'remoteip' => $request->getClientIp(),
                ],
            ]);
            $verifyData = $verifyResponse->toArray(false);
            $recaptchaScore = $verifyData['score'] ?? null;
            $recaptchaAction = $verifyData['action'] ?? null;
            if (empty($verifyData['success'])) {
                return $this->json(['error' => 'reCAPTCHA verification failed', 'recaptcha' => ['success' => false, 'score' => $recaptchaScore, 'action' => $recaptchaAction]], Response::HTTP_FORBIDDEN);
            }
            if (isset($verifyData['action']) && $verifyData['action'] !== 'comment_submit') {
                return $this->json(['error' => 'Invalid reCAPTCHA action', 'recaptcha' => ['success' => false, 'score' => $recaptchaScore, 'action' => $recaptchaAction]], Response::HTTP_FORBIDDEN);
            }
            if (isset($verifyData['score']) && $verifyData['score'] < 0.5) {
                return $this->json(['error' => 'reCAPTCHA score too low', 'recaptcha' => ['success' => false, 'score' => $recaptchaScore, 'action' => $recaptchaAction]], Response::HTTP_FORBIDDEN);
            }

            // Validate comment content length
            $contenu = trim($data['contenu']);
            if (strlen($contenu) < 1 || strlen($contenu) > 5000) {
                return $this->json(
                    ['error' => 'Comment content must be between 1 and 5000 characters'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Verify news exists
            $news = $this->entityManager->getRepository(News::class)->find($data['news_id']);
            if (!$news) {
                return $this->json(
                    ['error' => 'News item not found'],
                    Response::HTTP_NOT_FOUND
                );
            }

            // Create new comment
            $comment = new Commentaire();
            
            if (!empty($data['gif_url'])) {
                $comment->setGifUrl(trim($data['gif_url']));
            }

            // Encode parent ID if this is a reply
            if (!empty($data['parent_id']) && is_numeric($data['parent_id'])) {
                $contenu = '__PARENT__' . (int)$data['parent_id'] . '||' . $contenu;
            }

            // Filter profanities
            $contenu = $this->profanityFilter->filter($contenu);

            // Set comment fields
            $comment->setContenu($contenu);
            $comment->setDateCommentaire(new \DateTime());
            $comment->setUpvotes(0);
            $comment->setDownvotes(0);
            $comment->setNews($news);
            $current = $this->getUser();
            if ($current instanceof User) {
                $comment->setUser($current);
            }

            // Save to database
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            // Return response
            return $this->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'recaptcha' => [
                    'success' => true,
                    'score' => $recaptchaScore,
                    'action' => $recaptchaAction,
                ],
                'data' => [
                    'commentaire_id' => $comment->getCommentaireId(),
                    'contenu' => preg_replace('/^__PARENT__\d+\|\|/', '', (string) $comment->getContenu()),
                    'date_commentaire' => $comment->getDateCommentaire()?->format('Y-m-d H:i:s'),
                    'gif_url' => $comment->getGifUrl(),
                    'upvotes' => $comment->getUpvotes(),
                    'downvotes' => $comment->getDownvotes(),
                    'news_id' => $comment->getNews()->getNewsId(),
                    'parent_id' => !empty($data['parent_id']) && is_numeric($data['parent_id']) ? (int)$data['parent_id'] : null,
                    'user' => $comment->getUser() ? [
                        'id' => $comment->getUser()->getId(),
                        'username' => $comment->getUser()->getUsername(),
                        'avatar' => $comment->getUser()->getAvatar(),
                        'profile_url' => $this->generateUrl('app_profile_edit', ['id' => $comment->getUser()->getId()]),
                    ] : null,
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Error creating comment: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * EDIT COMMENT API ENDPOINT (FOR AJAX)
     * 
     * Route: POST /api/comments/{id}
     * Purpose: Update the text content of an existing comment
     * Returns: JSON response
     * 
     * @param int $id The comment ID to update
     * @param Request $request The HTTP request with JSON body
     * @return Response JSON response
     */
    #[Route('/api/comments/{id}', name: 'api_comments_edit', methods: ['POST'])]
    public function apiEdit(int $id, Request $request): Response
    {
        try {
            // Find comment
            $comment = $this->entityManager->getRepository(Commentaire::class)->find($id);
            if (!$comment) {
                return $this->json(
                    ['error' => 'Comment not found'],
                    Response::HTTP_NOT_FOUND
                );
            }

            // Authorization: only owner or admin
            $current = $this->getUser();
            $isOwner = $current instanceof User && $comment->getUser() && $comment->getUser()->getId() === $current->getId();
            if (!$isOwner && !$this->isGranted('ROLE_ADMIN')) {
                return $this->json(['error' => 'You are not allowed to edit this comment'], Response::HTTP_FORBIDDEN);
            }

            // Extract new content
            $data = json_decode($request->getContent(), true);

            if (!isset($data['contenu'])) {
                return $this->json(
                    ['error' => 'Missing required field: contenu'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Validate content
            $contenu = trim($data['contenu']);
            if (strlen($contenu) < 1 || strlen($contenu) > 5000) {
                return $this->json(
                    ['error' => 'Comment content must be between 1 and 5000 characters'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Filter profanities
            $contenu = $this->profanityFilter->filter($contenu);

            // Update comment
            $comment->setContenu($contenu);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Comment updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Error updating comment: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * DELETE COMMENT API ENDPOINT (FOR AJAX)
     * 
     * Route: DELETE /api/comments/{id} or POST /api/comments/{id}
     * Purpose: Permanently delete a comment
     * Returns: JSON response
     * 
     * @param int $id The comment ID to delete
     * @param Request $request The HTTP request
     * @return Response JSON response
     */
    #[Route('/api/comments/{id}', name: 'api_comments_delete', methods: ['DELETE', 'POST'])]
    public function apiDelete(int $id, Request $request): Response
    {
        try {
            // Find comment
            $comment = $this->entityManager->getRepository(Commentaire::class)->find($id);
            if (!$comment) {
                return $this->json(
                    ['error' => 'Comment not found'],
                    Response::HTTP_NOT_FOUND
                );
            }

            // Authorization: only owner or admin
            $current = $this->getUser();
            $isOwner = $current instanceof User && $comment->getUser() && $comment->getUser()->getId() === $current->getId();
            if (!$isOwner && !$this->isGranted('ROLE_ADMIN')) {
                return $this->json(['error' => 'You are not allowed to delete this comment'], Response::HTTP_FORBIDDEN);
            }

            if ($request->isMethod('POST')) {
                $tokenRaw = $request->request->get('_token');
                $token = is_string($tokenRaw) ? $tokenRaw : null;
                if (!$this->isCsrfTokenValid('delete_comment' . $id, $token)) {
                    return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
                }
            }

            // Delete comment
            $this->entityManager->remove($comment);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Error deleting comment: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * VOTE ON COMMENT API ENDPOINT (FOR AJAX)
     * 
     * Route: POST /api/comments/{id}/vote
     * Purpose: Create, change, or remove upvotes/downvotes on a comment
     * Returns: JSON response with updated vote counts
     * 
     * @param int $id The comment ID being voted on
     * @param Request $request The HTTP request with vote data
     * @return Response JSON response
     */
    #[Route('/api/comments/{id}/vote', name: 'api_comments_vote', methods: ['POST'])]
    public function apiVote(int $id, Request $request): Response
    {
        try {
            // Find comment
            $comment = $this->entityManager->getRepository(Commentaire::class)->find($id);
            if (!$comment) {
                return $this->json(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
            }

            // Extract vote data
            $data = json_decode($request->getContent(), true);
            
            $type = $data['type'] ?? null;
            $previous = $data['previous'] ?? null;
            $undo = !empty($data['undo']);

            // Validate vote type
            if (!in_array($type, ['up', 'down', null], true) && !$undo) {
                return $this->json(['error' => 'Invalid vote type'], Response::HTTP_BAD_REQUEST);
            }

            // Handle vote logic
            if ($undo && $previous) {
                if ($previous === 'up') {
                    $comment->setUpvotes(max(0, $comment->getUpvotes() - 1));
                } elseif ($previous === 'down') {
                    $comment->setDownvotes(max(0, $comment->getDownvotes() - 1));
                }
            } elseif ($previous && $previous !== $type) {
                if ($previous === 'up') {
                    $comment->setUpvotes(max(0, $comment->getUpvotes() - 1));
                } elseif ($previous === 'down') {
                    $comment->setDownvotes(max(0, $comment->getDownvotes() - 1));
                }

                if ($type === 'up') {
                    $comment->setUpvotes($comment->getUpvotes() + 1);
                } elseif ($type === 'down') {
                    $comment->setDownvotes($comment->getDownvotes() + 1);
                }
            } else {
                if ($type === 'up') {
                    $comment->setUpvotes($comment->getUpvotes() + 1);
                } elseif ($type === 'down') {
                    $comment->setDownvotes($comment->getDownvotes() + 1);
                }
            }

            // Save changes
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'upvotes' => $comment->getUpvotes(),
                'downvotes' => $comment->getDownvotes(),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error processing vote: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * AJAX Endpoint: Search for GIFs via Giphy API
     * 
     * Handles AJAX requests from the frontend to search for GIFs.
     * Returns JSON array of GIF objects with URLs ready for display and selection.
     * 
     * @param Request $request The HTTP request with query parameters
     * @param GiphyService $giphyService Service for Giphy API calls
     * 
     * @return Response JSON response with GIF results or error message
     */
    #[Route('/api/gifs/search', name: 'api_gifs_search', methods: ['GET'])]
    public function searchGifs(Request $request, GiphyService $giphyService): Response
    {
        try {
            // Get search query from request parameters
            $qRaw = $request->query->get('q');
            $query = is_string($qRaw) ? $qRaw : '';
            $limit = (int)($request->query->get('limit', 20));
            $offset = (int)($request->query->get('offset', 0));

            // Validate query parameter
            if (trim($query) === '') {
                return $this->json([
                    'error' => 'Search query is required',
                    'gifs' => [],
                ], Response::HTTP_BAD_REQUEST);
            }

            // Search GIFs via Giphy API
            $gifs = $giphyService->searchGifs($query, $limit, $offset);

            return $this->json([
                'success' => true,
                'query' => $query,
                'count' => count($gifs),
                'gifs' => $gifs,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error searching for GIFs: ' . $e->getMessage(),
                'gifs' => [],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * AJAX Endpoint: Get trending GIFs
     * 
     * Returns trending GIFs from Giphy without requiring a search query.
     * Used as default content when GIF picker opens.
     * 
     * @param Request $request The HTTP request with pagination parameters
     * @param GiphyService $giphyService Service for Giphy API calls
     * 
     * @return Response JSON response with trending GIF results
     */
    #[Route('/api/gifs/trending', name: 'api_gifs_trending', methods: ['GET'])]
    public function getTrendingGifs(Request $request, GiphyService $giphyService): Response
    {
        try {
            $limit = (int)($request->query->get('limit', 20));
            $offset = (int)($request->query->get('offset', 0));

            // Get trending GIFs
            $gifs = $giphyService->getTrendingGifs($limit, $offset);

            return $this->json([
                'success' => true,
                'count' => count($gifs),
                'gifs' => $gifs,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error fetching trending GIFs: ' . $e->getMessage(),
                'gifs' => [],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
