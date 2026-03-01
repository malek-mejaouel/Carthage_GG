<?php

namespace App\Controller;

use App\Entity\Team;
use App\Form\TeamType;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/teams')]
final class TeamController extends AbstractController
{
    public function __construct(
        private TeamRepository $teamRepository,
    ) {
    }

    #[Route('', name: 'team_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $teams = $this->teamRepository->findTeamsPaginated($page, 10);
        $total = count($this->teamRepository->findAll());

        return $this->render('team/index.html.twig', [
            'teams' => $teams,
            'total' => $total,
            'page' => $page,
            'perPage' => 10,
        ]);
    }

    #[Route('/new', name: 'team_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $team = new Team();
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->teamRepository->save($team, true);

            return $this->redirectToRoute('team_show', ['id' => $team->getTeamId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('team/new.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'team_show', methods: ['GET'])]
    public function show(Team $team): Response
    {
        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }

    #[Route('/{id}/edit', name: 'team_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Team $team): Response
    {
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->teamRepository->save($team, true);

            return $this->redirectToRoute('team_show', ['id' => $team->getTeamId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('team/edit.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'team_delete', methods: ['POST'])]
    public function delete(Request $request, Team $team): Response
    {
        $token = $request->request->get('_token');
        if (is_string($token) && $this->isCsrfTokenValid('delete' . $team->getTeamId(), $token)) {
            $this->teamRepository->remove($team, true);
        }

        return $this->redirectToRoute('team_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/by-user/{userId}', name: 'team_by_user', methods: ['GET'])]
    public function byUser(int $userId): Response
    {
        $teams = $this->teamRepository->findBy(['user' => $userId]);

        return $this->render('team/user-teams.html.twig', [
            'teams' => $teams,
            'userId' => $userId,
        ]);
    }
}
