<?php

namespace App\Controller;

use App\Entity\MatchPlayer;
use App\Form\MatchPlayerType;
use App\Repository\MatchPlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/match-players')]
final class MatchPlayerController extends AbstractController
{
    public function __construct(
        private MatchPlayerRepository $matchPlayerRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'match_player_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $matchPlayers = $this->matchPlayerRepository->findMatchPlayersPaginated($page, 10);
        $total = $this->matchPlayerRepository->getTotalCount();

        return $this->render('match_player/index.html.twig', [
            'matchPlayers' => $matchPlayers,
            'total' => $total,
            'page' => $page,
            'perPage' => 10,
        ]);
    }

    #[Route('/new', name: 'match_player_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $matchPlayer = new MatchPlayer();
        $form = $this->createForm(MatchPlayerType::class, $matchPlayer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->matchPlayerRepository->save($matchPlayer, true);

            return $this->redirectToRoute('match_player_show', ['id' => $matchPlayer->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('match_player/new.html.twig', [
            'matchPlayer' => $matchPlayer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'match_player_show', methods: ['GET'])]
    public function show(MatchPlayer $matchPlayer): Response
    {
        return $this->render('match_player/show.html.twig', [
            'matchPlayer' => $matchPlayer,
        ]);
    }

    #[Route('/{id}/edit', name: 'match_player_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MatchPlayer $matchPlayer): Response
    {
        $form = $this->createForm(MatchPlayerType::class, $matchPlayer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->matchPlayerRepository->save($matchPlayer, true);

            return $this->redirectToRoute('match_player_show', ['id' => $matchPlayer->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('match_player/edit.html.twig', [
            'matchPlayer' => $matchPlayer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'match_player_delete', methods: ['POST'])]
    public function delete(Request $request, MatchPlayer $matchPlayer): Response
    {
        if ($this->isCsrfTokenValid('delete' . $matchPlayer->getId(), $request->request->get('_token'))) {
            $this->matchPlayerRepository->remove($matchPlayer, true);
        }

        return $this->redirectToRoute('match_player_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/by-match/{matchId}', name: 'match_player_by_match', methods: ['GET'])]
    public function byMatch(int $matchId): Response
    {
        $matchPlayers = $this->matchPlayerRepository->findByMatch($matchId);

        return $this->render('match_player/by_match.html.twig', [
            'matchPlayers' => $matchPlayers,
            'matchId' => $matchId,
        ]);
    }

    #[Route('/by-user/{userId}', name: 'match_player_by_user', methods: ['GET'])]
    public function byUser(int $userId): Response
    {
        $matchPlayers = $this->matchPlayerRepository->findByUser($userId);

        return $this->render('match_player/by_user.html.twig', [
            'matchPlayers' => $matchPlayers,
            'userId' => $userId,
        ]);
    }
}
