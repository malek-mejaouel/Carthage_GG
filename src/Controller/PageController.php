<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\TournamentRepository;
use App\Repository\TeamRepository;
use App\Repository\MatchRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\PaymentIntent as StripePaymentIntent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Repository\EventRepository;

class PageController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(
        TournamentRepository $tournamentRepository,
        TeamRepository $teamRepository,
        MatchRepository $matchRepository,EventRepository $eventRepository,
    ): Response {
        $featuredTournaments = $tournamentRepository->findOngoingTournaments();
        if (count($featuredTournaments) === 0) {
            $featuredTournaments = $tournamentRepository->findUpcomingTournaments(6);
        }
        $topTeams = $teamRepository->findAllTeams();
        $upcomingMatches = $matchRepository->findUpcomingMatches(6);
        $recentMatches = $matchRepository->findPastMatches(6);
        $events = $eventRepository->findBy([], ['startAt' => 'ASC'], 6);
        return $this->render('index.html.twig', [
            'featured_tournaments' => $featuredTournaments,
            'top_teams' => $topTeams,
            'upcoming_matches' => $upcomingMatches,
            'recent_matches' => $recentMatches,
            'events' => $events,
        ]);
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('Dashboard/Dashboard.html.twig');
    }

    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        return $this->render('Login/sign_up/login.html.twig');
    }

    #[Route('/register', name: 'app_register')]
    public function register(): Response
    {
        return $this->render('Login/sign_up/sign_up.html.twig');
    }

    #[Route('/tournaments', name: 'app_tournaments')]
    public function tournaments(TournamentRepository $tournamentRepository): Response
    {
        $featuredTournaments = $tournamentRepository->findOngoingTournaments();
        if (count($featuredTournaments) === 0) {
            $featuredTournaments = $tournamentRepository->findUpcomingTournaments(6);
        }
        return $this->render('Tournament/tournaments.html.twig', [
            'featured_tournaments' => $featuredTournaments,
        ]);
    }

    #[Route('/teams', name: 'app_teams')]
    public function teams(TeamRepository $teamRepository): Response
    {
        $topTeams = $teamRepository->findAllTeams();
        return $this->render('Teams/teams.html.twig', [
            'top_teams' => $topTeams,
        ]);
    }
    #[Route('/games-link', name: 'app_games')]
    public function games(): Response
    {
        return $this->redirectToRoute('game_show');
    }

    #[Route('/matches', name: 'app_matches')]
    public function matches(\App\Repository\MatchRepository $matchRepository): Response
    {
        $upcoming = $matchRepository->findUpcomingMatches(20);
        $past = $matchRepository->findPastMatches(10);
        return $this->render('Matches/matches.html.twig', [
            'upcoming_matches' => $upcoming,
            'past_matches' => $past,
        ]);
    }

    #[Route('/leaderboard', name: 'app_leaderboard')]
    public function leaderboard(): Response
    {
        return $this->render('Leaderboard/leaderboard.html.twig');
    }

    #[Route('/support', name: 'app_support')]
    public function support(): Response
    {
        return $this->render('Support/support.html.twig');
    }

    
    #[Route('/news', name: 'app_news')]
    public function news(): Response
    {
        return $this->render('News/News.html.twig');
    }
    #[Route('/admin', name: 'app_admin')]
public function admin(
    Request $request,
    \App\Repository\UserRepository $userRepository
): Response {
    $q = $request->query->get('q');
    $status = $request->query->get('status');
    $sort = $request->query->get('sort', 'newest');

    $qb = $userRepository->createQueryBuilder('u');

    // 🔍 Search
    if ($q) {
        $qb->andWhere(
            'u.firstName LIKE :q 
             OR u.lastName LIKE :q 
             OR u.username LIKE :q 
             OR u.email LIKE :q'
        )
        ->setParameter('q', '%' . $q . '%');
    }

    // ✅ Status filter
    if ($status === 'active') {
        $qb->andWhere('u.isActive = true');
    } elseif ($status === 'inactive') {
        $qb->andWhere('u.isActive = false');
    }

    // 🔃 Sorting
    switch ($sort) {
        case 'oldest':
            $qb->orderBy('u.createdAt', 'ASC');
            break;
        case 'id_asc':
            $qb->orderBy('u.id', 'ASC');
            break;
        case 'id_desc':
            $qb->orderBy('u.id', 'DESC');
            break;
        default: // newest
            $qb->orderBy('u.createdAt', 'DESC');
    }

    $users = $qb->getQuery()->getResult();

    return $this->render('admin/section-admin.html.twig', [
        'users' => $users,
    ]);
}


    #[Route('/admin/profile-edit/{id}', name: 'app_profile_edit', requirements: ['id' => '\d+'])]
    public function profileEdit(int $id, UserRepository $users, Request $request, EntityManagerInterface $em): Response
    {
        $user = $users->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        if ($request->isMethod('POST')) {
            $uploaded = $request->files->get('avatar');
            if ($uploaded) {
                $ext = $uploaded->guessExtension() ?: 'bin';
                $filename = bin2hex(random_bytes(8)).'.'.$ext;
                $targetDir = $this->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'avatars';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0775, true);
                }
                $uploaded->move($targetDir, $filename);
                $user->setAvatar('uploads/avatars/'.$filename);
                $em->persist($user);
                $em->flush();
                return $this->redirectToRoute('app_profile_edit', ['id' => $id]);
            }
        }

        return $this->render('admin/profile-edit.html.twig', ['user' => $user]);
    }
    #[Route('/admin/profile-change-password/{id}', name: 'app_profile_change_password', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function changePassword(int $id, Request $request, UserRepository $users, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $users->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('change_password_' . $id, $submittedToken)) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
        $current = (string) $request->request->get('current_password');
        $new = (string) $request->request->get('new_password');
        $confirm = (string) $request->request->get('confirm_password');
        if ($new === '' || $confirm === '' || $new !== $confirm) {
            $this->addFlash('error', 'Passwords do not match');
            return $this->redirectToRoute('app_profile_edit', ['id' => $id]);
        }
        if (strlen($new) < 6) {
            $this->addFlash('error', 'Password must be at least 6 characters');
            return $this->redirectToRoute('app_profile_edit', ['id' => $id]);
        }
        if (!$passwordHasher->isPasswordValid($user, $current)) {
            $this->addFlash('error', 'Current password is incorrect');
            return $this->redirectToRoute('app_profile_edit', ['id' => $id]);
        }
        $hashed = $passwordHasher->hashPassword($user, $new);
        $user->setPassword($hashed);
        $user->setUpdatedAt(new \DateTime());
        $em->persist($user);
        $em->flush();
        $this->addFlash('success', 'Password updated successfully');
        return $this->redirectToRoute('app_profile_edit', ['id' => $id]);
    }
      #[Route('/admin/user-delete/{id}', name: 'app_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteUser(
        int $id, 
        \App\Repository\UserRepository $userRepository, 
        \Doctrine\ORM\EntityManagerInterface $entityManager, 
        \Symfony\Component\HttpFoundation\Request $request,
        \Symfony\Component\Security\Core\Security $security,
        TokenStorageInterface $tokenStorage
    ): Response {
        $user = $userRepository->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_user_' . $id, $submittedToken)) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        // Check if the user being deleted is the currently logged-in user
        $currentUser = $security->getUser();
        $isCurrentUser = false;
        if ($currentUser instanceof \App\Entity\User) {
            $isCurrentUser = ($currentUser->getId() === $user->getId());
        }

        // Delete user's avatar file if exists
        if ($user->getAvatar()) {
            $projectDir = $this->getParameter('kernel.project_dir');
            $avatarPath = $projectDir . '/public/' . $user->getAvatar();
            if (file_exists($avatarPath) && is_file($avatarPath)) {
                unlink($avatarPath);
            }
        }

        // If deleting the current user, invalidate session and clear token BEFORE deletion
        if ($isCurrentUser) {
            // Clear the security token first
            $tokenStorage->setToken(null);
            // Then invalidate the session
            $request->getSession()->invalidate();
        }

        $entityManager->remove($user);
        $entityManager->getConnection()->executeStatement(
            'DELETE FROM teams WHERE user_id = :id',
            ['id' => $user->getId()],
            ['id' => ParameterType::INTEGER]
        );
        $entityManager->flush();
        $entityManager->clear(); // Clear entity manager to prevent stale references

        // If deleting the current user, redirect to login
        if ($isCurrentUser) {
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute('app_admin');
    }
     #[Route('/store', name: 'app_store')]
    public function store(
        \Symfony\Component\HttpFoundation\Request $request,
        \App\Repository\ProductRepository $products,
        \App\Repository\CategoryRepository $categories
    ): Response {
        $q = $request->query->get('q');
        $categoryId = $request->query->getInt('category');
        $sort = $request->query->get('sort', 'newest');
        $featuredOnly = $request->query->getBoolean('featured', false);
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = 8;
        $items = $products->searchPaginated($q, $categoryId ?: null, $sort, $page, $perPage, $featuredOnly);
        $total = $products->countSearch($q, $categoryId ?: null, $featuredOnly);
        $featured = $featuredOnly ? [] : $products->findBy(['status' => 'active', 'isFeatured' => true], ['createdAt' => 'DESC'], 8);
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $cartCount = 0;
        foreach ($cart as $pid => $qty) { $cartCount += (int)$qty; }
        return $this->render('Store/store.html.twig', [
            'products' => $items,
            'categories' => $categories->findAllOrdered(),
            'selected_category' => $categoryId ?: null,
            'q' => $q,
            'sort' => $sort,
            'featured_only' => $featuredOnly,
            'cart_count' => $cartCount,
            'featured' => $featured,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'has_prev' => $page > 1,
            'has_next' => ($page * $perPage) < $total,
        ]);
    }

    #[Route('/store/cart', name: 'app_store_cart')]
    public function storeCart(\Symfony\Component\HttpFoundation\Request $request, \App\Repository\ProductRepository $products): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $shippingMethod = $session->get('shipping_method', 'standard');
        $checkoutEmail = $session->get('checkout_email', null);
        $cartItems = [];
        $subtotal = 0.0;
        foreach ($cart as $productId => $qty) {
            $product = $products->find($productId);
            if ($product) {
                $price = (float) $product->getPrice();
                $discount = (float) $product->getDiscount();
                $final = max(0.0, $price - ($price * $discount / 100));
                $subtotal += $final * $qty;
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_price' => $final,
                    'total' => $final * $qty,
                ];
            }
        }
        $shipping = 0.0;
        if ($subtotal > 0) {
            $shipping = $shippingMethod === 'express' ? 24.99 : 9.99;
        }
        $tax = round($subtotal * 0.083, 2);
        $total = $subtotal + $shipping + $tax;
        return $this->render('Store/store-cart.html.twig', [
            'cart_items' => $cartItems,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
            'shipping_method' => $shippingMethod,
            'checkout_email' => $checkoutEmail,
        ]);
    }

    #[Route('/store/cart/summary', name: 'app_store_cart_summary', methods: ['GET'])]
    public function storeCartSummary(\Symfony\Component\HttpFoundation\Request $request, \App\Repository\ProductRepository $products): JsonResponse
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $shippingMethod = $session->get('shipping_method', 'standard');
        $items = [];
        $subtotal = 0.0;
        foreach ($cart as $productId => $qty) {
            $product = $products->find($productId);
            if (!$product) { continue; }
            $price = (float)$product->getPrice();
            $discount = (float)$product->getDiscount();
            $final = max(0.0, $price - ($price * $discount / 100));
            $subtotal += $final * (int)$qty;
            $items[] = [
                'id' => (int)$productId,
                'name' => (string)$product->getName(),
                'qty' => (int)$qty,
                'unit_price' => (float)$final,
                'total' => (float)$final * (int)$qty,
            ];
        }
        $shipping = $subtotal > 0 ? ($shippingMethod === 'express' ? 24.99 : 9.99) : 0.0;
        $tax = round($subtotal * 0.083, 2);
        $total = $subtotal + $shipping + $tax;
        return $this->json([
            'items' => $items,
            'shipping_method' => $shippingMethod,
            'shipping' => $shipping,
            'tax' => $tax,
            'subtotal' => $subtotal,
            'total' => $total,
            'currency' => 'usd',
        ]);
    }

    #[Route('/store/checkout', name: 'app_store_checkout', methods: ['POST'])]
    public function storeCheckout(Request $request, \App\Repository\ProductRepository $products): Response
    {
        $secretKey = (string)$this->getParameter('stripe.secret_key');
        $successUrl = (string)$this->getParameter('stripe.success_url');
        $cancelUrl = (string)$this->getParameter('stripe.cancel_url');
        if (!$secretKey) {
            $this->addFlash('error', 'Stripe is not configured.');
            return $this->redirectToRoute('app_store_cart');
        }
        Stripe::setApiKey($secretKey);
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        if (empty($cart)) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('app_store_cart');
        }
        $domain = $request->getSchemeAndHttpHost();
        $lineItems = [];
        $shippingMethod = $request->getSession()->get('shipping_method', 'standard');
        $checkoutEmail = $request->getSession()->get('checkout_email', null);
        $shippingCost = 0.0;
        // Re-compute subtotal while building line items
        $subtotal = 0.0;
        foreach ($cart as $productId => $qty) {
            $product = $products->find($productId);
            if (!$product) { continue; }
            $price = (float)$product->getPrice();
            $discount = (float)$product->getDiscount();
            $final = max(0.0, $price - ($price * $discount / 100));
            $subtotal += $final * (int)$qty;
            $img = $product->getImage();
            $imageUrl = null;
            if ($img) {
                if (str_starts_with($img, 'http')) {
                    $imageUrl = $img;
                } elseif (str_starts_with($img, '/uploads/') || str_starts_with($img, 'uploads/')) {
                    $imageUrl = $domain . (str_starts_with($img, '/') ? $img : ('/' . $img));
                } else {
                    $imageUrl = $domain . '/uploads/products/' . $img;
                }
            }
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $product->getName(),
                        'images' => $imageUrl ? [$imageUrl] : [],
                    ],
                    'unit_amount' => max(0, (int)round($final * 100)),
                ],
                'quantity' => max(1, (int)$qty),
            ];
        }
        if ($subtotal > 0) {
            $shippingCost = $shippingMethod === 'express' ? 2499 : 999; // in cents
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $shippingMethod === 'express' ? 'Express Shipping' : 'Standard Shipping',
                    ],
                    'unit_amount' => $shippingCost,
                ],
                'quantity' => 1,
            ];
        }
        try {
            $emailForReceipt = null;
            $user = $this->getUser();
            if ($user instanceof \App\Entity\User && method_exists($user, 'getEmail') && $user->getEmail()) {
                $emailForReceipt = (string)$user->getEmail();
            } elseif ($checkoutEmail) {
                $emailForReceipt = (string)$checkoutEmail;
            }
            $checkoutSession = StripeCheckoutSession::create([
                'mode' => 'payment',
                'line_items' => $lineItems,
                'success_url' => rtrim($successUrl, '/') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $cancelUrl,
                'customer_email' => $emailForReceipt ?: null,
                'payment_intent_data' => $emailForReceipt ? [
                    'receipt_email' => $emailForReceipt,
                ] : null,
            ]);
            return $this->redirect($checkoutSession->url);
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Stripe error: ' . $e->getMessage());
            return $this->redirectToRoute('app_store_cart');
        }
    }

    #[Route('/store/checkout/success', name: 'app_store_checkout_success', methods: ['GET'])]
    public function storeCheckoutSuccess(Request $request, \App\Repository\ProductRepository $products, \Doctrine\ORM\EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $sessionParam = (string) $request->query->get('session_id', '');
        $receiptUrl = null;
        $secretKey = (string)$this->getParameter('stripe.secret_key');
        $stripePaymentIntentId = null;
        $n8nWebhook = (string)$this->getParameter('n8n.receipt_webhook_url');
        if ($secretKey && $sessionParam) {
            Stripe::setApiKey($secretKey);
            try {
                $checkoutSession = \Stripe\Checkout\Session::retrieve($sessionParam);
                if (!empty($checkoutSession->payment_intent)) {
                    $pi = StripePaymentIntent::retrieve($checkoutSession->payment_intent);
                    $stripePaymentIntentId = $checkoutSession->payment_intent;
                    if (!empty($pi->charges->data)) {
                        $charge = $pi->charges->data[0];
                        $receiptUrl = $charge->receipt_url ?? null;
                    }
                }
            } catch (\Throwable $e) {
                // ignore and proceed
            }
        }
        $cartSession = $request->getSession();
        $cart = $cartSession->get('cart', []);
        $shippingMethod = $cartSession->get('shipping_method', 'standard');
        $itemsSnapshot = [];
        $subtotal = 0.0;
        foreach ($cart as $productId => $qty) {
            $product = $products->find($productId);
            if (!$product) { continue; }
            $price = (float)$product->getPrice();
            $discount = (float)$product->getDiscount();
            $final = max(0.0, $price - ($price * $discount / 100));
            $subtotal += $final * (int)$qty;
            $itemsSnapshot[] = [
                'id' => $productId,
                'name' => $product->getName(),
                'qty' => (int)$qty,
                'unit_price' => $final,
                'total' => $final * (int)$qty,
            ];
        }
        $shipping = $subtotal > 0 ? ($shippingMethod === 'express' ? 24.99 : 9.99) : 0.0;
        $tax = round($subtotal * 0.083, 2);
        $total = $subtotal + $shipping + $tax;
        if ($this->getUser() instanceof \App\Entity\User && $total > 0) {
            $order = new \App\Entity\Order();
            $order->setUser($this->getUser());
            $order->setCurrency('usd');
            $order->setAmount($total);
            $order->setItems($itemsSnapshot);
            $order->setShippingMethod($shippingMethod);
            $order->setShipping($shipping);
            $order->setTax($tax);
            $order->setStatus('paid');
            $order->setStripeSessionId($sessionParam ?: null);
            $order->setStripePaymentIntentId($stripePaymentIntentId);
            $em->persist($order);
            $em->flush();
        }
        $recipient = null;
        $user = $this->getUser();
        if ($user instanceof \App\Entity\User && method_exists($user, 'getEmail') && $user->getEmail()) {
            $recipient = (string)$user->getEmail();
        } else {
            $recipient = (string)$cartSession->get('checkout_email', '');
        }
        if ($recipient && filter_var($recipient, FILTER_VALIDATE_EMAIL) && $total > 0) {
            $lines = '';
            foreach ($itemsSnapshot as $it) {
                $lines .= sprintf(
                    '<tr><td style="padding:8px;border:1px solid #ddd;">%s</td><td style="padding:8px;border:1px solid #ddd;text-align:center;">%d</td><td style="padding:8px;border:1px solid #ddd;text-align:right;">$%0.2f</td><td style="padding:8px;border:1px solid #ddd;text-align:right;">$%0.2f</td></tr>',
                    htmlspecialchars($it['name'], ENT_QUOTES),
                    (int)$it['qty'],
                    (float)$it['unit_price'],
                    (float)$it['total']
                );
            }
            $body = sprintf(
                '<div style="font-family:Arial,sans-serif;color:#e4e4e7;background:#0a0a0f;padding:20px;">
                    <h2 style="color:#D4AF37;margin:0 0 10px;">Your CarthageGG Receipt</h2>
                    <p>Thank you for your purchase. Here is your receipt.</p>
                    <table style="border-collapse:collapse;width:100%%;margin:15px 0;">%s</table>
                    <p style="margin:8px 0;">Shipping: $%0.2f</p>
                    <p style="margin:8px 0;">Tax: $%0.2f</p>
                    <p style="margin:8px 0;font-weight:bold;">Total: $%0.2f</p>
                    %s
                    <p style="margin-top:16px;"><a href="%s" style="display:inline-block;padding:10px 16px;background:#D4AF37;color:#0a0a0f;text-decoration:none;border-radius:6px;">View Receipt</a></p>
                </div>',
                '<thead><tr><th style="padding:8px;border:1px solid #ddd;text-align:left;">Item</th><th style="padding:8px;border:1px solid #ddd;">Qty</th><th style="padding:8px;border:1px solid #ddd;text-align:right;">Unit</th><th style="padding:8px;border:1px solid #ddd;text-align:right;">Total</th></tr></thead><tbody>'.$lines.'</tbody>',
                (float)$shipping,
                (float)$tax,
                (float)$total,
                $stripePaymentIntentId ? sprintf('<p>Payment Intent: %s</p>', htmlspecialchars($stripePaymentIntentId, ENT_QUOTES)) : '',
                $receiptUrl ?: $request->getSchemeAndHttpHost()
            );
            $email = (new Email())
                ->from('no-reply@carthagegg.local')
                ->to($recipient)
                ->subject('Your CarthageGG Order Receipt')
                ->html($body);
            try { $mailer->send($email); } catch (\Throwable $e) {}
            if ($n8nWebhook && str_starts_with($n8nWebhook, 'http')) {
                $executionMode = str_contains($n8nWebhook, '/webhook-test/') ? 'test' : 'prod';
                $payload = [
                    'type' => 'order.receipt',
                    'email' => $recipient,
                    'order' => [
                        'items' => array_map(function(array $it) {
                            return [
                                'id' => (int)$it['id'],
                                'name' => (string)$it['name'],
                                'qty' => (int)$it['qty'],
                                'unit_price' => (float)$it['unit_price'],
                                'total' => (float)$it['total'],
                            ];
                        }, $itemsSnapshot),
                        'shipping_method' => (string)$shippingMethod,
                        'shipping' => (float)$shipping,
                        'tax' => (float)$tax,
                        'total' => (float)$total,
                        'currency' => 'usd',
                    ],
                    'webhookUrl' => $n8nWebhook,
                    'executionMode' => $executionMode,
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                ];
                $json = json_encode($payload);
                $ctx = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                        'content' => $json,
                        'timeout' => 5,
                    ],
                ]);
                try { @file_get_contents($n8nWebhook, false, $ctx); } catch (\Throwable $e) {}
            }
        }
        $cartSession->set('cart', []);
        return $this->render('Store/store-success.html.twig', [
            'receipt_url' => $receiptUrl,
        ]);
    }

    #[Route('/store/checkout/cancel', name: 'app_store_checkout_cancel', methods: ['GET'])]
    public function storeCheckoutCancel(): Response
    {
        $this->addFlash('error', 'Payment cancelled.');
        return $this->redirectToRoute('app_store_cart');
    }

    #[Route('/store/cart/add/{id}', name: 'app_store_cart_add')]
    public function addToCart(int $id, \Symfony\Component\HttpFoundation\Request $request, \App\Repository\ProductRepository $products): Response
    {
        $product = $products->find($id);
        if (!$product || $product->getStatus() !== 'active' || $product->getStock() <= 0) {
            if ($request->isXmlHttpRequest() || str_contains((string)$request->headers->get('accept', ''), 'application/json')) {
                return $this->json(['ok' => false, 'error' => 'Unavailable'], 400);
            }
            return $this->redirectToRoute('app_store');
        }
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $cart[$id] = ($cart[$id] ?? 0) + 1;
        $session->set('cart', $cart);
        if ($request->isXmlHttpRequest() || str_contains((string)$request->headers->get('accept', ''), 'application/json')) {
            $count = 0; foreach ($cart as $pid => $qty) { $count += (int)$qty; }
            return $this->json(['ok' => true, 'count' => $count]);
        }
        return $this->redirectToRoute('app_store_cart');
    }

    #[Route('/store/cart/remove/{id}', name: 'app_store_cart_remove')]
    public function removeFromCart(int $id, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        unset($cart[$id]);
        $session->set('cart', $cart);
        return $this->redirectToRoute('app_store_cart');
    }

    #[Route('/store/cart/shipping', name: 'app_store_cart_shipping', methods: ['POST'])]
    public function setShippingMethod(\Symfony\Component\HttpFoundation\Request $request): Response
    {
        $method = $request->request->get('shipping');
        if (!in_array($method, ['standard', 'express'], true)) {
            $this->addFlash('error', 'Invalid shipping method.');
            return $this->redirectToRoute('app_store_cart');
        }
        $session = $request->getSession();
        $session->set('shipping_method', $method);
        $this->addFlash('success', 'Shipping method updated.');
        return $this->redirectToRoute('app_store_cart');
    }

    #[Route('/store/cart/email', name: 'app_store_cart_email', methods: ['POST'])]
    public function setCheckoutEmail(\Symfony\Component\HttpFoundation\Request $request): Response
    {
        $email = trim((string)$request->request->get('email', ''));
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Please enter a valid email address.');
            return $this->redirectToRoute('app_store_cart');
        }
        $session = $request->getSession();
        $session->set('checkout_email', $email ?: null);
        $this->addFlash('success', 'Email updated.');
        return $this->redirectToRoute('app_store_cart');
    }

    #[Route('/admin/store-management', name: 'app_store_management')]
    public function storeManagement(): Response
    {
        return $this->render('Store/store-management.html.twig');
    }

    
}
