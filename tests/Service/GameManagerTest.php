<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Game;
use App\Service\GameManager;
use PHPUnit\Framework\TestCase;

class GameManagerTest extends TestCase
{
    public function testValidGame(): void
    {
        $g = new Game();
        $g->setName('StarCraft');
        $g->setGenre('RTS');
        $m = new GameManager();
        $this->assertTrue($m->validate($g));
    }

    public function testEmptyName(): void
    {
        $g = new Game();
        $g->setName('');
        $m = new GameManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($g);
    }

    public function testShortName(): void
    {
        $g = new Game();
        $g->setName('Go');
        $m = new GameManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($g);
    }

    public function testLongGenre(): void
    {
        $g = new Game();
        $g->setName('StarCraft');
        $g->setGenre(str_repeat('a', 101));
        $m = new GameManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($g);
    }
}
