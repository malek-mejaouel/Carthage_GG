<?php

namespace App\Controller;

use App\Message\GenerateScheduleMessage;
use App\Repository\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class TournamentScheduleController extends AbstractController
{
    public function __construct(
        private TournamentRepository $tournamentRepository,
        private MessageBusInterface $bus
    ) {
    }

    #[Route('/tournament/{id}/schedule', name: 'tournament_schedule_generate', methods: ['POST'])]
    public function schedule(int $id): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);
        if (!$tournament) {
            return $this->json(['error' => 'Tournament not found'], Response::HTTP_NOT_FOUND);
        }

        $this->bus->dispatch(new GenerateScheduleMessage($id));

        return $this->json(['status' => 'Scheduling started']);
    }
}

