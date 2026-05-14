<?php

namespace App\Service;

use App\Entity\Team;

class TeamManager
{
    public function validate(Team $team): bool
    {
        $name = $team->getTeamName();
        if (empty($name)) {
            throw new \InvalidArgumentException('Team name is required');
        }
        if (strlen($name) < 3) {
            throw new \InvalidArgumentException('Team name must be at least 3 characters');
        }
        $logo = $team->getLogo();
        if ($logo !== null && strlen($logo) > 255) {
            throw new \InvalidArgumentException('Logo must not exceed 255 characters');
        }
        return true;
    }
}
