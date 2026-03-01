<?php

namespace App\Controller;

use App\Entity\TeamPlayer;
use App\Form\TeamPlayerType;
use App\Repository\TeamPlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/team-players')]
final class TeamPlayerController extends AbstractController
{
    public function __construct(
        private TeamPlayerRepository $teamPlayerRepository,
    ) {
    }

    #[Route('', name: 'team_player_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $teamPlayers = $this->teamPlayerRepository->findTeamPlayersPaginated($page, 10);
        $total = $this->teamPlayerRepository->getTotalCount();

        return $this->render('team_player/index.html.twig', [
            'teamPlayers' => $teamPlayers,
            'total' => $total,
            'page' => $page,
            'perPage' => 10,
        ]);
    }

    #[Route('/new', name: 'team_player_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $teamPlayer = new TeamPlayer();
        $form = $this->createForm(TeamPlayerType::class, $teamPlayer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->teamPlayerRepository->save($teamPlayer, true);

            return $this->redirectToRoute('team_player_show', ['id' => $teamPlayer->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('team_player/new.html.twig', [
            'teamPlayer' => $teamPlayer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'team_player_show', methods: ['GET'])]
    public function show(TeamPlayer $teamPlayer): Response
    {
        return $this->render('team_player/show.html.twig', [
            'teamPlayer' => $teamPlayer,
        ]);
    }

    #[Route('/{id}/edit', name: 'team_player_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TeamPlayer $teamPlayer): Response
    {
        $form = $this->createForm(TeamPlayerType::class, $teamPlayer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->teamPlayerRepository->save($teamPlayer, true);

            return $this->redirectToRoute('team_player_show', ['id' => $teamPlayer->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('team_player/edit.html.twig', [
            'teamPlayer' => $teamPlayer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'team_player_delete', methods: ['POST'])]
    public function delete(Request $request, TeamPlayer $teamPlayer): Response
    {
        $token = $request->request->get('_token');
        if (is_string($token) && $this->isCsrfTokenValid('delete' . $teamPlayer->getId(), $token)) {
            $this->teamPlayerRepository->remove($teamPlayer, true);
        }

        return $this->redirectToRoute('team_player_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/by-team/{teamId}', name: 'team_player_by_team', methods: ['GET'])]
    public function byTeam(int $teamId): Response
    {
        $teamPlayers = $this->teamPlayerRepository->findByTeam($teamId);
        $memberCount = count($teamPlayers);

        return $this->render('team_player/by_team.html.twig', [
            'teamPlayers' => $teamPlayers,
            'teamId' => $teamId,
            'memberCount' => $memberCount,
        ]);
    }

    #[Route('/by-user/{userId}', name: 'team_player_by_user', methods: ['GET'])]
    public function byUser(int $userId): Response
    {
        $teamPlayers = $this->teamPlayerRepository->findByUser($userId);

        return $this->render('team_player/by_user.html.twig', [
            'teamPlayers' => $teamPlayers,
            'userId' => $userId,
        ]);
    }
}
