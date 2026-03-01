<?php

namespace App\Controller;

use App\Entity\Game;
use App\Form\GameType;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/games')]
final class GameController extends AbstractController
{
    public function __construct(
        private GameRepository $gameRepository,
    ) {
    }

    #[Route('', name: 'game_index', methods: ['GET'])]
    #[Route('', name: 'game_show', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $games = $this->gameRepository->findAll();
        $total = count($games);
        $page = 1;
        $perPage = $total ?: 10;

        return $this->render('game/index.html.twig', [
            'games' => $games,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    #[Route('/new', name: 'game_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $game = new Game();
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->gameRepository->save($game, true);

            return $this->redirectToRoute('game_detail', ['id' => $game->getGameId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('game/new.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'game_detail', methods: ['GET'])]
    public function show(Game $game): Response
    {
        return $this->render('game/show.html.twig', [
            'game' => $game,
        ]);
    }

    #[Route('/{id}/edit', name: 'game_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Game $game): Response
    {
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->gameRepository->save($game, true);

            return $this->redirectToRoute('game_detail', ['id' => $game->getGameId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('game/edit.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'game_delete', methods: ['POST'])]
    public function delete(Request $request, Game $game): Response
    {
        $tokenRaw = $request->request->get('_token');
        $token = is_string($tokenRaw) ? $tokenRaw : null;
        if ($this->isCsrfTokenValid('delete' . $game->getGameId(), $token)) {
            $this->gameRepository->remove($game, true);
        }

        return $this->redirectToRoute('game_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/search', name: 'game_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $keywordRaw = $request->query->get('q', '');
        $keyword = is_string($keywordRaw) ? $keywordRaw : '';
        $games = [];

        if ($keyword) {
            $games = $this->gameRepository->searchByName($keyword);
        }

        return $this->render('game/search.html.twig', [
            'games' => $games,
            'keyword' => $keyword,
        ]);
    }
}
