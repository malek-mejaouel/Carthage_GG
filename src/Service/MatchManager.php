<?php

namespace App\Service;

use App\Entity\GameMatch;

class MatchManager
{
    public function validate(GameMatch $match): bool
    {
        if ($match->getTeamA() === null) {
            throw new \InvalidArgumentException('Team A is required');
        }
        if ($match->getTeamB() === null) {
            throw new \InvalidArgumentException('Team B is required');
        }
        if ($match->getGame() === null) {
            throw new \InvalidArgumentException('Game is required');
        }
        if ($match->getMatchDate() === null) {
            throw new \InvalidArgumentException('Match date is required');
        }
        return true;
    }
}
