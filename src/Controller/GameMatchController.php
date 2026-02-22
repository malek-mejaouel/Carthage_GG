<?php

namespace App\Controller;

use App\Entity\GameMatch;
use App\Form\MatchType;
use App\Repository\MatchRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/matches')]
final class GameMatchController extends AbstractController
{
    public function __construct(
        private MatchRepository $matchRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'match_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $matches = $this->matchRepository->findMatchesPaginated($page, 10);
        $total = $this->matchRepository->getTotalCount();

        return $this->render('match/index.html.twig', [
            'matches' => $matches,
            'total' => $total,
            'page' => $page,
            'perPage' => 10,
        ]);
    }

    #[Route('/new', name: 'match_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $match = new GameMatch();
        $form = $this->createForm(MatchType::class, $match);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->matchRepository->save($match, true);

            return $this->redirectToRoute('match_show', ['id' => $match->getMatchId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('match/new.html.twig', [
            'match' => $match,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'match_show', methods: ['GET'])]
    public function show(GameMatch $match): Response
    {
        return $this->render('match/show.html.twig', [
            'match' => $match,
        ]);
    }

    #[Route('/{id}/edit', name: 'match_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, GameMatch $match): Response
    {
        $form = $this->createForm(MatchType::class, $match);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->matchRepository->save($match, true);

            return $this->redirectToRoute('match_show', ['id' => $match->getMatchId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('match/edit.html.twig', [
            'match' => $match,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'match_delete', methods: ['POST'])]
    public function delete(Request $request, GameMatch $match): Response
    {
        if ($this->isCsrfTokenValid('delete' . $match->getMatchId(), $request->request->get('_token'))) {
            $this->matchRepository->remove($match, true);
        }

        return $this->redirectToRoute('match_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/upcoming', name: 'match_upcoming', methods: ['GET'])]
    public function upcoming(): Response
    {
        $matches = $this->matchRepository->findUpcomingMatches(20);

        return $this->render('match/upcoming.html.twig', [
            'matches' => $matches,
        ]);
    }

    #[Route('/past', name: 'match_past', methods: ['GET'])]
    public function past(): Response
    {
        $matches = $this->matchRepository->findPastMatches(20);

        return $this->render('match/past.html.twig', [
            'matches' => $matches,
        ]);
    }
}
