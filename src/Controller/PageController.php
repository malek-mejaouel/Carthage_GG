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

class PageController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(
        TournamentRepository $tournamentRepository,
        TeamRepository $teamRepository,
        MatchRepository $matchRepository,
    ): Response {
        $featuredTournaments = $tournamentRepository->findOngoingTournaments();
        if (count($featuredTournaments) === 0) {
            $featuredTournaments = $tournamentRepository->findUpcomingTournaments(6);
        }
        $topTeams = $teamRepository->findAllTeams();
        $upcomingMatches = $matchRepository->findUpcomingMatches(6);
        $recentMatches = $matchRepository->findPastMatches(6);
        return $this->render('index.html.twig', [
            'featured_tournaments' => $featuredTournaments,
            'top_teams' => $topTeams,
            'upcoming_matches' => $upcomingMatches,
            'recent_matches' => $recentMatches,
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
}
