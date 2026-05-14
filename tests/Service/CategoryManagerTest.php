<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Category;
use App\Service\CategoryManager;
use PHPUnit\Framework\TestCase;

class CategoryManagerTest extends TestCase
{
    public function testValidCategory(): void
    {
        $c = new Category();
        $c->setName('Strategy');
        $m = new CategoryManager();
        $this->assertTrue($m->validate($c));
    }

    public function testEmptyName(): void
    {
        $c = new Category();
        $c->setName('');
        $m = new CategoryManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($c);
    }

    public function testShortName(): void
    {
        $c = new Category();
        $c->setName('A');
        $m = new CategoryManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($c);
    }

    public function testLongName(): void
    {
        $c = new Category();
        $c->setName(str_repeat('a', 101));
        $m = new CategoryManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($c);
    }
}
