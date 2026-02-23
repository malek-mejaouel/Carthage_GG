<?php

namespace App\Controller;
use App\Entity\News;
use App\Repository\NewsRepository;
use App\Repository\GameRepository;
use App\Repository\MatchRepository;
use App\Repository\CommentaireRepository;
use App\Repository\TournamentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserBanService;
use App\Repository\UserRepository as AppUserRepository;
use App\Entity\User as AppUser;
use App\Repository\ProductRepository;
use Stripe\Stripe;
use Stripe\PaymentIntent as StripePaymentIntent;
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
      #[Route('/admin/dashboard/news', name: 'app_admin_dash_news')]
    public function newsSection(NewsRepository $newsRepository): Response
    {
        $news = $newsRepository->findAllOrderedByDate();
        return $this->render('admin/section-news.html.twig', [
            'news' => $news
        ]);
    }
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(UserRepository $users): Response
    {
         return $this->render('admin/dashboard.html.twig', [
            'users' => $users->findAll(),
        ]);
    }

    #[Route('/admin/dashboard/admin', name: 'app_admin_dash_admin')]
    public function adminSection(UserRepository $users): Response
    {
        return $this->render('admin/section-admin.html.twig', [
            'users' => $users->findAll(),
        ]);
    }
    #[Route('/admin/user/{id}/ban', name: 'admin_user_ban', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function banUser(
        int $id,
        Request $request,
        AppUserRepository $userRepository,
        UserBanService $banService
    ): Response {
        $admin = $this->getUser();
        if (!$admin instanceof AppUser) {
            return new JsonResponse(['success' => false, 'message' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }
        $target = $userRepository->find($id);
        if (!$target instanceof AppUser) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        $data = json_decode($request->getContent(), true) ?: [];
        $value = (int)($data['value'] ?? 0);
        $unit = trim((string)($data['unit'] ?? 'minutes'));
        $reason = isset($data['reason']) ? trim((string)$data['reason']) : null;
        try {
            $banService->applyBan($admin, $target, $value, $unit, $reason);
            return new JsonResponse(['success' => true, 'remaining' => $target->getRemainingBanTime()]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/admin/user/{id}/unban', name: 'admin_user_unban', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function unbanUser(
        int $id,
        AppUserRepository $userRepository,
        UserBanService $banService
    ): Response {
        $admin = $this->getUser();
        if (!$admin instanceof AppUser) {
            return new JsonResponse(['success' => false, 'message' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }
        $target = $userRepository->find($id);
        if (!$target instanceof AppUser) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        try {
            $banService->removeBan($admin, $target);
            return new JsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/admin/dashboard/game', name: 'app_admin_dash_game')]
    public function gameSection(GameRepository $gameRepository): Response
    {
        $games = $gameRepository->findAll();
        return $this->render('admin/section-game.html.twig', [
            'games' => $games,
        ]);
    }

    #[Route('/admin/dashboard/matches', name: 'app_admin_dash_matches')]
    public function matchesSection(MatchRepository $matchRepository): Response
    {
        $matches = $matchRepository->findAll();
        return $this->render('admin/section-matches.html.twig', [
            'matches' => $matches,
        ]);
    }

    #[Route('/admin/dashboard/tournament', name: 'app_admin_dash_tournament')]
    public function tournamentSection(Request $request, TournamentRepository $tournaments): Response
    {
        $page = $request->query->getInt('page', 1);
        $perPage = 9;
        $list = $tournaments->findTournamentsPaginated($page, $perPage);
        $total = $tournaments->getTotalCount();
        return $this->render('admin/section-tournament.html.twig', [
            'tournaments' => $list,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }



    #[Route('/admin/dashboard/comments', name: 'app_admin_dash_comments')]
    public function commentsSection(CommentaireRepository $commentaireRepository): Response
    {
        $comments = $commentaireRepository->findAllOrderedByDate();
        return $this->render('admin/section-comments.html.twig', [
            'comments' => $comments,
        ]);
    }

    #[Route('/admin/dashboard/posts', name: 'app_admin_dash_posts')]
    public function postsSection(NewsRepository $newsRepository): Response
    {
        $posts = $newsRepository->findAllOrderedByDate();
        return $this->render('admin/section-posts.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/admin/dashboard/teams', name: 'app_admin_dash_teams')]
    public function teamsSection(): Response
    {
        return $this->render('admin/section-teams.html.twig');
    }

    #[Route('/admin/dashboard/places', name: 'app_admin_dash_places')]
    public function placesSection(): Response
    {
        return $this->render('admin/section-places.html.twig');
    }
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

        return $this->render('news/edit.html.twig', [
            'news' => $news,
            'errors' => [],
            'success' => false,
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
     #[Route('/admin/dashboard/products', name: 'app_admin_dash_products')]
    public function productsSection(Request $request, ProductRepository $products): Response
    {
        $featuredOnly = $request->query->getBoolean('featured', false);
        $criteria = [];
        if ($featuredOnly) { $criteria['isFeatured'] = true; }
        $list = $products->findBy($criteria, ['isFeatured' => 'DESC', 'createdAt' => 'DESC']);
        $inventoryLabels = [];
        $inventoryData = [];
        $categoryCounts = [];
        foreach ($list as $p) {
            $inventoryLabels[] = (string)$p->getName();
            $inventoryData[] = (int)$p->getStock();
            $cat = $p->getCategory();
            $catName = $cat ? (string)$cat->getName() : 'Uncategorized';
            $categoryCounts[$catName] = ($categoryCounts[$catName] ?? 0) + 1;
        }
        $categoryLabels = array_keys($categoryCounts);
        $categoryData = array_values($categoryCounts);
        $categoryColorsPalette = ['#D4AF37', '#3B82F6', '#A855F7', '#22C55E', '#F97316', '#EF4444', '#14B8A6', '#F59E0B'];
        $categoryColors = [];
        foreach ($categoryLabels as $i => $_) {
            $categoryColors[] = $categoryColorsPalette[$i % count($categoryColorsPalette)];
        }
        return $this->render('admin/section-products.html.twig', [
            'products' => $list,
            'inventory_labels' => $inventoryLabels,
            'inventory_data' => $inventoryData,
            'category_labels' => $categoryLabels,
            'category_data' => $categoryData,
            'category_colors' => $categoryColors,
        ]);
    }

    #[Route('/admin/dashboard/products/export/pdf', name: 'app_admin_products_export_pdf')]
    public function productsExportPdf(ProductRepository $products): Response
    {
        $list = $products->findBy([], ['createdAt' => 'DESC']);
        return $this->render('admin/export/products-pdf.html.twig', [
            'products' => $list,
            'generated_at' => (new \DateTime())->format('Y-m-d H:i'),
        ]);
    }

    #[Route('/admin/dashboard/sales', name: 'app_admin_dash_sales')]
    public function salesSection(): Response
    {
        $secretKey = (string)$this->getParameter('stripe.secret_key');
        $revenueLabels = [];
        $revenueData = [];
        $paymentLabels = [];
        $paymentData = [];
        $transactions = [];
        if ($secretKey) {
            Stripe::setApiKey($secretKey);
            try {
                $months = [];
                $now = new \DateTimeImmutable('now');
                for ($i = 5; $i >= 0; $i--) {
                    $m = $now->modify("-{$i} months");
                    $key = $m->format('Y-m');
                    $months[$key] = 0.0;
                }
                $createdGte = (new \DateTimeImmutable('first day of -5 months'))->setTime(0,0)->getTimestamp();
                $pis = StripePaymentIntent::all([
                    'limit' => 100,
                    'created' => ['gte' => $createdGte],
                ]);
                $methodCounts = [];
                foreach ($pis->data as $pi) {
                    $ts = (int)($pi->created ?? 0);
                    $monthKey = (new \DateTimeImmutable("@{$ts}"))->format('Y-m');
                    $amount = (float)($pi->amount_received ?? 0) / 100.0;
                    if (isset($months[$monthKey])) {
                        $months[$monthKey] += $amount;
                    }
                    $status = (string)$pi->status;
                    $customerEmail = (string)($pi->receipt_email ?? ($pi->charges->data[0]->billing_details->email ?? ''));
                    $methodType = 'unknown';
                    if (!empty($pi->charges->data)) {
                        $pm = $pi->charges->data[0]->payment_method_details;
                        if ($pm && isset($pm->type)) {
                            $methodType = (string)$pm->type;
                        }
                    } elseif (!empty($pi->payment_method_types)) {
                        $methodType = (string)($pi->payment_method_types[0] ?? 'unknown');
                    }
                    $methodCounts[$methodType] = ($methodCounts[$methodType] ?? 0) + 1;
                    $transactions[] = [
                        'id' => (string)$pi->id,
                        'email' => $customerEmail ?: 'N/A',
                        'date' => (new \DateTimeImmutable("@{$ts}"))->format('M d, Y'),
                        'amount' => number_format($amount, 2),
                        'status' => $status,
                        'method' => ucfirst($methodType),
                    ];
                }
                foreach ($months as $key => $val) {
                    $dt = \DateTimeImmutable::createFromFormat('Y-m', $key);
                    $revenueLabels[] = $dt ? $dt->format('M') : $key;
                    $revenueData[] = round($val, 2);
                }
                foreach ($methodCounts as $k => $v) {
                    $paymentLabels[] = ucfirst($k);
                    $paymentData[] = $v;
                }
            } catch (\Throwable $e) {
                // leave arrays empty on error
            }
        }
        return $this->render('admin/section-sales.html.twig', [
            'revenue_labels' => $revenueLabels,
            'revenue_data' => $revenueData,
            'payment_labels' => $paymentLabels,
            'payment_data' => $paymentData,
            'transactions' => $transactions,
        ]);
    }

    #[Route('/admin/dashboard/sales/export/pdf', name: 'app_admin_sales_export_pdf')]
    public function salesExportPdf(): Response
    {
        $secretKey = (string)$this->getParameter('stripe.secret_key');
        $transactions = [];
        if ($secretKey) {
            Stripe::setApiKey($secretKey);
            try {
                $pis = StripePaymentIntent::all([
                    'limit' => 100,
                ]);
                foreach ($pis->data as $pi) {
                    $ts = (int)($pi->created ?? 0);
                    $amount = (float)($pi->amount_received ?? 0) / 100.0;
                    $status = (string)$pi->status;
                    $customerEmail = (string)($pi->receipt_email ?? ($pi->charges->data[0]->billing_details->email ?? ''));
                    $methodType = 'unknown';
                    if (!empty($pi->charges->data)) {
                        $pm = $pi->charges->data[0]->payment_method_details;
                        if ($pm && isset($pm->type)) {
                            $methodType = (string)$pm->type;
                        }
                    } elseif (!empty($pi->payment_method_types)) {
                        $methodType = (string)($pi->payment_method_types[0] ?? 'unknown');
                    }
                    $transactions[] = [
                        'id' => (string)$pi->id,
                        'email' => $customerEmail ?: 'N/A',
                        'date' => (new \DateTimeImmutable("@{$ts}"))->format('M d, Y H:i'),
                        'amount' => number_format($amount, 2),
                        'status' => $status,
                        'method' => ucfirst($methodType),
                    ];
                }
            } catch (\Throwable $e) {
            }
        }
        return $this->render('admin/export/sales-pdf.html.twig', [
            'transactions' => $transactions,
            'generated_at' => (new \DateTime())->format('Y-m-d H:i'),
        ]);
    }
      #[Route('/admin/dashboard/streams', name: 'app_admin_dash_streams')]
    public function streamsSection(\App\Repository\StreamRepository $streams): Response
    {
        return $this->render('admin/section-streams.html.twig', [
            'streams' => $streams->findLatest(50),
        ]);
    }

    #[Route('/admin/streams/new', name: 'app_admin_streams_new', methods: ['POST'])]
    public function addStreamAdmin(
        Request $request,
        EntityManagerInterface $entityManager,
        \App\Repository\UserRepository $users,
        SluggerInterface $slugger
    ): Response {
        $data = $request->request->all();
        $streamData = $data['stream'] ?? [];
        if (!is_array($streamData)) {
            $streamData = [];
            foreach ($data as $key => $val) {
                if (is_string($key) && str_starts_with($key, 'stream[')) {
                    $field = str_replace(['stream[', ']'], '', $key);
                    $streamData[$field] = $val;
                }
            }
        }
        $normalized = $this->normalizeStreamFields($streamData);
        if (!empty($normalized)) {
            $streamData = array_merge($streamData, $normalized);
        }
        $title = trim($streamData['title'] ?? '');
        $url = trim($streamData['url'] ?? '');
        if ($title === '' || $url === '') {
            return $this->redirectToRoute('app_admin_dash_streams');
        }
        $s = new \App\Entity\Stream();
        $s->setTitle($title);
        $platformValue = (string)($streamData['platform'] ?? ((isset($streamData['youtube_video_id']) && trim((string)$streamData['youtube_video_id']) !== '') ? 'youtube' : ((isset($streamData['channel_name']) && trim((string)$streamData['channel_name']) !== '') ? 'twitch' : 'unknown')));
        $s->setPlatform($platformValue);
        $s->setChannelName($streamData['channel_name'] ?? null);
        $s->setYoutubeVideoId($streamData['youtube_video_id'] ?? null);
        // Handle thumbnail upload
        $thumbFile = null;
        if ($request->files->has('stream')) {
            $streamFiles = $request->files->all()['stream'] ?? null;
            if (is_array($streamFiles) && isset($streamFiles['thumbnail'])) {
                $thumbFile = $streamFiles['thumbnail'];
            }
        }
        if ($thumbFile && $thumbFile->isValid()) {
            $originalFilename = pathinfo($thumbFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $thumbFile->guessExtension();
            try {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/streams';
                if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }
                $thumbFile->move($uploadDir, $newFilename);
                $s->setThumbnail('/uploads/streams/' . $newFilename);
            } catch (FileException $e) {
                // ignore upload failure; keep thumbnail null
            }
        }
        $s->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->persist($s);
        $entityManager->flush();
        return $this->redirectToRoute('app_admin_dash_streams');
    }

    #[Route('/admin/streams/new', name: 'app_admin_streams_new_get', methods: ['GET'])]
    public function addStreamAdminGet(): Response
    {
        return $this->redirectToRoute('app_admin_dash_streams');
    }

    #[Route('/admin/streams/{id}/edit', name: 'app_admin_streams_edit', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function editStreamAdmin(int $id, \App\Repository\StreamRepository $streams): Response
    {
        $s = $streams->find($id);
        if (!$s) {
            return $this->redirectToRoute('app_admin_dash_streams');
        }
        return $this->render('admin/stream-edit.html.twig', [
            'stream' => $s,
        ]);
    }

    #[Route('/admin/streams/{id}/edit', name: 'app_admin_streams_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateStreamAdmin(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        \App\Repository\StreamRepository $streams,
        \App\Repository\UserRepository $users,
        SluggerInterface $slugger
    ): Response {
        $s = $streams->find($id);
        if (!$s) {
            return $this->redirectToRoute('app_admin_dash_streams');
        }
        $data = $request->request->all();
        $streamData = $data['stream'] ?? [];
        if (!is_array($streamData)) {
            $streamData = [];
            foreach ($data as $key => $val) {
                if (is_string($key) && str_starts_with($key, 'stream[')) {
                    $field = str_replace(['stream[', ']'], '', $key);
                    $streamData[$field] = $val;
                }
            }
        }
        $normalized = $this->normalizeStreamFields($streamData);
        if (!empty($normalized)) {
            $streamData = array_merge($streamData, $normalized);
        }
        $title = trim($streamData['title'] ?? $s->getTitle());
        $url = trim($streamData['url'] ?? '');
        $s->setTitle($title);
        $s->setPlatform($streamData['platform'] ?? $s->getPlatform());
        $s->setChannelName($streamData['channel_name'] ?? $s->getChannelName());
        $s->setYoutubeVideoId($streamData['youtube_video_id'] ?? $s->getYoutubeVideoId());
        // Handle new thumbnail upload (optional)
        $thumbFile = null;
        if ($request->files->has('stream')) {
            $streamFiles = $request->files->all()['stream'] ?? null;
            if (is_array($streamFiles) && isset($streamFiles['thumbnail'])) {
                $thumbFile = $streamFiles['thumbnail'];
            }
        }
        if ($thumbFile && $thumbFile->isValid()) {
            $originalFilename = pathinfo($thumbFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $thumbFile->guessExtension();
            try {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/streams';
                if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }
                $thumbFile->move($uploadDir, $newFilename);
                $s->setThumbnail('/uploads/streams/' . $newFilename);
            } catch (FileException $e) {
                // ignore upload failure; keep existing thumbnail
            }
        }
        $s->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();
        return $this->redirectToRoute('app_admin_dash_streams');
    }

    #[Route('/admin/streams/{id}/delete', name: 'app_admin_streams_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteStreamAdmin(
        int $id,
        EntityManagerInterface $entityManager,
        \App\Repository\StreamRepository $streams
    ): Response {
        $s = $streams->find($id);
        if ($s) {
            $entityManager->remove($s);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_admin_dash_streams');
    }
     private function normalizeStreamFields(array $streamData): array
    {
        $out = [];
        $url = isset($streamData['url']) ? trim((string)$streamData['url']) : '';
        if ($url !== '') {
            $ytId = $this->parseYouTubeId($url);
            if ($ytId) {
                $out['platform'] = 'youtube';
                $out['youtube_video_id'] = $ytId;
            } else {
                $twCh = $this->parseTwitchChannel($url);
                if ($twCh) {
                    $out['platform'] = 'twitch';
                    $out['channel_name'] = $twCh;
                }
            }
        }
        if (isset($streamData['channel_name'])) {
            $ch = trim((string)$streamData['channel_name']);
            if ($ch !== '' && str_contains($ch, 'twitch.tv')) {
                $parsed = $this->parseTwitchChannel($ch);
                if ($parsed) {
                    $out['channel_name'] = $parsed;
                    $out['platform'] = 'twitch';
                }
            }
        }
        if (isset($streamData['youtube_video_id'])) {
            $vid = trim((string)$streamData['youtube_video_id']);
            if ($vid !== '' && (str_contains($vid, 'youtube.com') || str_contains($vid, 'youtu.be'))) {
                $parsed = $this->parseYouTubeId($vid);
                if ($parsed) {
                    $out['youtube_video_id'] = $parsed;
                    $out['platform'] = 'youtube';
                }
            }
        }
        return $out;
    }

    private function parseYouTubeId(string $url): ?string
    {
        $u = parse_url($url);
        $host = $u['host'] ?? '';
        $path = $u['path'] ?? '';
        $query = $u['query'] ?? '';
        if ($host && str_contains($host, 'youtu.be')) {
            $seg = array_values(array_filter(explode('/', $path)));
            return $seg[0] ?? null;
        }
        if ($host && str_contains($host, 'youtube.com')) {
            parse_str($query, $qs);
            if (!empty($qs['v'])) {
                return $qs['v'];
            }
            $parts = array_values(array_filter(explode('/', $path)));
            if (count($parts) >= 2 && ($parts[0] === 'embed' || $parts[0] === 'shorts')) {
                return $parts[1];
            }
        }
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }
        return null;
    }

    private function parseTwitchChannel(string $url): ?string
    {
        $u = parse_url($url);
        $host = $u['host'] ?? '';
        $path = $u['path'] ?? '';
        if ($host && str_contains($host, 'twitch.tv')) {
            $seg = array_values(array_filter(explode('/', $path)));
            return $seg[0] ?? null;
        }
        $trimmed = trim($url);
        if ($trimmed !== '' && !str_contains($trimmed, '/')) {
            return $trimmed;
        }
        return null;
    }

}
