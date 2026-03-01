<?php

namespace App\Controller;

use App\Service\PredictionService;
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
    private const RIVALRY_THRESHOLD = 3;

    public function __construct(
        private MatchRepository $matchRepository,
    ) {
    }

    #[Route('', name: 'match_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $matches = $this->matchRepository->findMatchesPaginated($page, 10);
        $total = $this->matchRepository->getTotalCount();

        $rivalryFlags = $this->computeRivalryFlags($matches);

        return $this->render('Matches/index.html.twig', [
            'matches' => $matches,
            'rivalryFlags' => $rivalryFlags,
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
            return $this->redirectToRoute('match_show', ['id' => $match->getMatchId()]);
        }

        return $this->render('Matches/new.html.twig', [
            'match' => $match,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'match_show', methods: ['GET'])]
    public function show(GameMatch $match, PredictionService $predictionService): Response
    {
        $prediction = null;
        if ($match->getTeamA() && $match->getTeamB()) {
            $prediction = $predictionService->predictWinner($match->getTeamA(), $match->getTeamB());
        }

        // Calcul du flag de rivalité pour ce match
        $count = 0;
        if ($match->getTeamA() && $match->getTeamB()) {
            $count = $this->matchRepository->countPreviousMeetings($match->getTeamA(), $match->getTeamB());
        }
        $isRivalry = $count >= self::RIVALRY_THRESHOLD;

        return $this->render('Matches/show.html.twig', [
            'match' => $match,
            'prediction' => $prediction,
            'isRivalry' => $isRivalry,  // on passe directement le booléen
        ]);
    }

    #[Route('/{id}/edit', name: 'match_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, GameMatch $match): Response
    {
        $form = $this->createForm(MatchType::class, $match);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->matchRepository->save($match, true);
            return $this->redirectToRoute('match_show', ['id' => $match->getMatchId()]);
        }

        return $this->render('Matches/edit.html.twig', [
            'match' => $match,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'match_delete', methods: ['POST'])]
    public function delete(Request $request, GameMatch $match): Response
    {
        $tokenRaw = $request->request->get('_token');
        $token = is_string($tokenRaw) ? $tokenRaw : null;
        if ($this->isCsrfTokenValid('delete' . $match->getMatchId(), $token)) {
            $this->matchRepository->remove($match, true);
        }

        return $this->redirectToRoute('match_index');
    }

    #[Route('/upcoming', name: 'match_upcoming', methods: ['GET'])]
    public function upcoming(): Response
    {
        $matches = $this->matchRepository->findUpcomingMatches(20);
        $rivalryFlags = $this->computeRivalryFlags($matches);

        return $this->render('Matches/upcoming.html.twig', [
            'matches' => $matches,
            'rivalryFlags' => $rivalryFlags,
        ]);
    }

    #[Route('/past', name: 'match_past', methods: ['GET'])]
    public function past(): Response
    {
        $matches = $this->matchRepository->findPastMatches(20);
        $rivalryFlags = $this->computeRivalryFlags($matches);

        return $this->render('Matches/past.html.twig', [
            'matches' => $matches,
            'rivalryFlags' => $rivalryFlags,
        ]);
    }

    /**
     * Calcule les flags de rivalité pour une collection de matchs.
     *
     * @param GameMatch[] $matches
     * @return array<int, bool> Tableau associatif [matchId => bool]
     */
    private function computeRivalryFlags(array $matches): array
    {
        $flags = [];
        foreach ($matches as $match) {
            if ($match->getTeamA() && $match->getTeamB()) {
                $count = $this->matchRepository->countPreviousMeetings($match->getTeamA(), $match->getTeamB());
                $flags[(int) $match->getMatchId()] = $count >= self::RIVALRY_THRESHOLD;
            } else {
                $flags[(int) $match->getMatchId()] = false;
            }
        }
        return $flags;
    }
}
