<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Form\TournamentType;
use App\Repository\TournamentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournaments')]
final class TournamentController extends AbstractController
{
    public function __construct(
        private TournamentRepository $tournamentRepository,
    ) {
    }

    #[Route('/list', name: 'tournament_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $tournaments = $this->tournamentRepository->findTournamentsPaginated($page, 10);
        $total = $this->tournamentRepository->getTotalCount();

        return $this->render('tournament/index.html.twig', [
            'tournaments' => $tournaments,
            'total' => $total,
            'page' => $page,
            'perPage' => 10,
        ]);
    }

    #[Route('/new', name: 'tournament_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $tournament = new Tournament();
        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tournamentRepository->save($tournament, true);

            return $this->redirectToRoute('tournament_show', ['id' => $tournament->getTournamentId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tournament/new.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'tournament_show', methods: ['GET'])]
    public function show(Tournament $tournament): Response
    {
        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournament,
        ]);
    }

    #[Route('/{id}/edit', name: 'tournament_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tournament $tournament): Response
    {
        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tournamentRepository->save($tournament, true);

            return $this->redirectToRoute('tournament_show', ['id' => $tournament->getTournamentId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tournament/edit.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'tournament_delete', methods: ['POST'])]
    public function delete(Request $request, Tournament $tournament): Response
    {
        $token = $request->request->get('_token');
        if (is_string($token) && $this->isCsrfTokenValid('delete' . $tournament->getTournamentId(), $token)) {
            $this->tournamentRepository->remove($tournament, true);
        }

        return $this->redirectToRoute('tournament_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/upcoming/list', name: 'tournament_upcoming', methods: ['GET'])]
    public function upcoming(): Response
    {
        $tournaments = $this->tournamentRepository->findUpcomingTournaments(20);

        return $this->render('tournament/upcoming.html.twig', [
            'tournaments' => $tournaments,
        ]);
    }

    #[Route('/ongoing/list', name: 'tournament_ongoing', methods: ['GET'])]
    public function ongoing(): Response
    {
        $tournaments = $this->tournamentRepository->findOngoingTournaments();

        return $this->render('tournament/ongoing.html.twig', [
            'tournaments' => $tournaments,
        ]);
    }

    #[Route('/completed/list', name: 'tournament_completed', methods: ['GET'])]
    public function completed(): Response
    {
        $tournaments = $this->tournamentRepository->findCompletedTournaments(20);

        return $this->render('tournament/completed.html.twig', [
            'tournaments' => $tournaments,
        ]);
    }
}
