<?php

namespace App\MessageHandler;

use App\Message\GenerateScheduleMessage;
use App\Service\SchedulerService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GenerateScheduleHandler
{
    public function __construct(private SchedulerService $schedulerService)
    {
    }

    public function __invoke(GenerateScheduleMessage $message): void
    {
        $this->schedulerService->generateForTournament(
            $message->getTournamentId(),
            $message->getFormat(),
            $message->getTimeSlots()
        );
    }
}

