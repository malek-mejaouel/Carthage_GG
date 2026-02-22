<?php

namespace App\Message;

final class GenerateScheduleMessage
{
    public function __construct(
        private int $tournamentId,
        private string $format = 'round_robin',
        private array $timeSlots = [],
    ) {
    }

    public function getTournamentId(): int
    {
        return $this->tournamentId;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getTimeSlots(): array
    {
        return $this->timeSlots;
    }
}

