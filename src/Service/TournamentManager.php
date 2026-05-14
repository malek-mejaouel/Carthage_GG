<?php

namespace App\Service;

use App\Entity\Tournament;

class TournamentManager
{
    public function validate(Tournament $tournament): bool
    {
        $name = $tournament->getTournamentName();
        if (empty($name)) {
            throw new \InvalidArgumentException('Name is required');
        }
        if (strlen($name) < 3) {
            throw new \InvalidArgumentException('Name must be at least 3 characters');
        }
        if ($tournament->getGame() === null) {
            throw new \InvalidArgumentException('Game is required');
        }
        $start = $tournament->getStartDate();
        $end = $tournament->getEndDate();
        if ($start && $end && $end < $start) {
            throw new \InvalidArgumentException('End date must be after start date');
        }
        return true;
    }
}
