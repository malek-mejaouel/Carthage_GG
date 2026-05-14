<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Game;
use App\Entity\Tournament;
use App\Service\TournamentManager;
use PHPUnit\Framework\TestCase;

class TournamentManagerTest extends TestCase
{
    private function validTournament(): Tournament
    {
        $t = new Tournament();
        $t->setTournamentName('Winter Cup');
        $t->setGame((new Game())->setName('StarCraft'));
        $t->setStartDate(new \DateTime('2026-01-01'));
        $t->setEndDate(new \DateTime('2026-01-02'));
        return $t;
    }

    public function testValidTournament(): void
    {
        $m = new TournamentManager();
        $this->assertTrue($m->validate($this->validTournament()));
    }

    public function testEmptyName(): void
    {
        $t = $this->validTournament();
        $t->setTournamentName('');
        $m = new TournamentManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($t);
    }

    public function testShortName(): void
    {
        $t = $this->validTournament();
        $t->setTournamentName('AB');
        $m = new TournamentManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($t);
    }

    public function testMissingGame(): void
    {
        $t = $this->validTournament();
        $t->setGame(null);
        $m = new TournamentManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($t);
    }

    public function testInvalidDates(): void
    {
        $t = $this->validTournament();
        $t->setStartDate(new \DateTime('2026-01-03'));
        $t->setEndDate(new \DateTime('2026-01-02'));
        $m = new TournamentManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($t);
    }
}
