<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Game;
use App\Entity\GameMatch;
use App\Entity\Team;
use App\Service\MatchManager;
use PHPUnit\Framework\TestCase;

class MatchManagerTest extends TestCase
{
    private function validMatch(): GameMatch
    {
        $m = new GameMatch();
        $m->setTeamA((new Team())->setTeamName('Alpha'));
        $m->setTeamB((new Team())->setTeamName('Beta'));
        $m->setGame((new Game())->setName('StarCraft'));
        $m->setMatchDate(new \DateTime());
        return $m;
    }

    public function testValidMatch(): void
    {
        $m = new MatchManager();
        $this->assertTrue($m->validate($this->validMatch()));
    }

    public function testMissingTeamA(): void
    {
        $match = $this->validMatch();
        $match->setTeamA(null);
        $m = new MatchManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($match);
    }

    public function testMissingTeamB(): void
    {
        $match = $this->validMatch();
        $match->setTeamB(null);
        $m = new MatchManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($match);
    }

    public function testMissingGame(): void
    {
        $match = $this->validMatch();
        $match->setGame(null);
        $m = new MatchManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($match);
    }

    public function testMissingDate(): void
    {
        $match = $this->validMatch();
        $match->setMatchDate(null);
        $m = new MatchManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($match);
    }
}
