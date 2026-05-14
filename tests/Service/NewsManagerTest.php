<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\News;
use App\Service\NewsManager;
use PHPUnit\Framework\TestCase;

class NewsManagerTest extends TestCase
{
    private function validNews(): News
    {
        $n = new News();
        $n->setTitre('Title');
        $n->setContenu(str_repeat('c', 20));
        $n->setCategorie('Tournament');
        $n->setImage('image.jpg');
        return $n;
    }

    public function testValidNews(): void
    {
        $m = new NewsManager();
        $this->assertTrue($m->validate($this->validNews()));
    }

    public function testEmptyTitle(): void
    {
        $n = $this->validNews();
        $n->setTitre('');
        $m = new NewsManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($n);
    }

    public function testShortContent(): void
    {
        $n = $this->validNews();
        $n->setContenu('short');
        $m = new NewsManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($n);
    }

    public function testEmptyCategory(): void
    {
        $n = $this->validNews();
        $n->setCategorie('');
        $m = new NewsManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($n);
    }

    public function testMissingImage(): void
    {
        $n = $this->validNews();
        $n->setImage('');
        $m = new NewsManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($n);
    }
}
