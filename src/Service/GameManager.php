<?php

namespace App\Service;

use App\Entity\Game;

class GameManager
{
    public function validate(Game $game): bool
    {
        $name = $game->getName();
        if (empty($name)) {
            throw new \InvalidArgumentException('Name is required');
        }
        if (strlen($name) < 3) {
            throw new \InvalidArgumentException('Name must be at least 3 characters');
        }
        $genre = $game->getGenre();
        if ($genre !== null && strlen($genre) > 100) {
            throw new \InvalidArgumentException('Genre must not exceed 100 characters');
        }
        return true;
    }
}
