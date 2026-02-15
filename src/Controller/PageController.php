<?php
// src/Controller/PageController.php

namespace App\Controller;

use App\Repository\TournamentRepository;
use App\Repository\TeamRepository;
use App\Repository\MatchRepository;
use App\Repository\TeamPlayerRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private TournamentRepository $tournamentRepository,
        private TeamRepository $teamRepository,
        private MatchRepository $matchRepository,
         private TeamPlayerRepository $teamplayerRepository,
    ) {
    }

    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        // ----- FEATURED TOURNAMENTS (UPCOMING) -----
        // Get the 3 most recent tournaments (any date) – they will be labelled Live/Upcoming correctly in the template
$featuredTournaments = $this->tournamentRepository
    ->createQueryBuilder('t')
    ->orderBy('t.start_date', 'DESC')
    ->setMaxResults(3)
    ->getQuery()
    ->getResult();

    if (count($featuredTournaments) === 0) {
    $featuredTournaments = $this->tournamentRepository
        ->createQueryBuilder('t')
        ->orderBy('t.start_date', 'DESC')
        ->setMaxResults(3)
        ->getQuery()
        ->getResult();
}

        // ----- TOTAL PRIZE POOL -----
        $totalPrizePool = $this->tournamentRepository
            ->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.prize_pool), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        // ----- TOTAL COUNTS -----
        $totalPlayers     = $this->teamplayerRepository->count([]);
        $totalTournaments = $this->tournamentRepository->count([]);
        $totalTeams       = $this->teamRepository->count([]);

        // ----- TOP TEAMS (ordered by team name) -----
        $topTeams = $this->teamRepository
            ->createQueryBuilder('tm')
            ->orderBy('tm.team_name', 'ASC')
            ->setMaxResults(4)
            ->getQuery()
            ->getResult();

        // ----- UPCOMING MATCHES -----
        $upcomingMatches = $this->matchRepository
    ->createQueryBuilder('m')
    ->where('m.match_date > :now')
    ->setParameter('now', new \DateTime())
    ->orderBy('m.match_date', 'ASC')
    ->setMaxResults(3)
    ->getQuery()
    ->getResult();

    if (count($upcomingMatches) === 0) {
    $upcomingMatches = $this->matchRepository
        ->createQueryBuilder('m')
        ->orderBy('m.match_date', 'DESC')
        ->setMaxResults(3)
        ->getQuery()
        ->getResult();
}

        $recentUsers = $this->userRepository->getRecentlyCreatedUsers(7, 5);

        return $this->render('index.html.twig', [
            'recentUsers'          => $recentUsers,
            'featured_tournaments' => $featuredTournaments,
            'total_players'        => number_format($totalPlayers),
            'total_tournaments'    => number_format($totalTournaments),
            'total_prize_pool'     => number_format((float) $totalPrizePool),
            'total_teams'          => number_format($totalTeams),
            'top_teams'            => $topTeams,
            'upcoming_matches'     => $upcomingMatches,
        ]);
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('Dashboard/Dashboard.html.twig', [
            'totalUsers' => $this->userRepository->getTotalCount(),
        ]);
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

    #[Route('/leaderboard', name: 'app_leaderboard')]
    public function leaderboard(): Response
    {
        $sortedUsers = $this->userRepository->getRecentlyActiveUsers(999, 100);
        return $this->render('Leaderboard/leaderboard.html.twig', [
            'leaderboard' => $sortedUsers,
        ]);
    }

    #[Route('/support', name: 'app_support')]
    public function support(): Response
    {
        return $this->render('Support/support.html.twig');
    }

    #[Route('/store', name: 'app_store')]
    public function store(): Response
    {
        return $this->render('Store/store.html.twig');
    }

    #[Route('/store/cart', name: 'app_store_cart')]
    public function storeCart(): Response
    {
        return $this->render('Store/store-cart.html.twig');
    }

    #[Route('/admin/store-management', name: 'app_store_management')]
    public function storeManagement(): Response
    {
        return $this->render('Store/store-management.html.twig');
    }

    #[Route('/admin', name: 'app_admin')]
    public function admin(): Response
    {
        return $this->render('admin/admin.html.twig', [
            'totalUsers' => $this->userRepository->getTotalCount(),
            'activeUsers' => $this->userRepository->findActiveUsers(),
            'recentlyCreated' => $this->userRepository->getRecentlyCreatedUsers(30, 10),
        ]);
    }

    #[Route('/admin/profile-edit/{id}', name: 'app_profile_edit', requirements: ['id' => '\d+'])]
    public function profileEdit(int $id): Response
    {
        $user = $this->userRepository->findById($id);
        return $this->render('admin/profile-edit.html.twig', [
            'userId' => $id,
            'user' => $user,
        ]);
    }
}