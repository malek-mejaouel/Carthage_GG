<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
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

    #[Route('/news', name: 'app_news')]
    public function news(): Response
    {
        return $this->render('Tournament/news.html.twig');
    }

    #[Route('/teams', name: 'app_teams')]
    public function teams(): Response
    {
        return $this->render('Teams/teams.html.twig');
    }

    #[Route('/matches', name: 'app_matches')]
    public function matches(): Response
    {
        return $this->render('Matches/matches.html.twig');
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
        return $this->render('admin/admin.html.twig');
    }

    #[Route('/admin/profile-edit/{id}', name: 'app_profile_edit', requirements: ['id' => '\d+'])]
    public function profileEdit(int $id): Response
    {
        return $this->render('admin/profile-edit.html.twig', [
            'userId' => $id,
        ]);
    }
}
