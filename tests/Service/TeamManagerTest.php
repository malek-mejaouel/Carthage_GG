<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Team;
use App\Service\TeamManager;
use PHPUnit\Framework\TestCase;

class TeamManagerTest extends TestCase
{
    public function testValidTeam(): void
    {
        $t = new Team();
        $t->setTeamName('Wolves');
        $m = new TeamManager();
        $this->assertTrue($m->validate($t));
    }

    public function testEmptyName(): void
    {
        $t = new Team();
        $t->setTeamName('');
        $m = new TeamManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($t);
    }

    public function testShortName(): void
    {
        $t = new Team();
        $t->setTeamName('AB');
        $m = new TeamManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($t);
    }

    public function testLongLogo(): void
    {
        $t = new Team();
        $t->setTeamName('Wolves');
        $t->setLogo(str_repeat('a', 256));
        $m = new TeamManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($t);
    }
}
