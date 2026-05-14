<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Product;
use App\Service\ProductManager;
use PHPUnit\Framework\TestCase;

class ProductManagerTest extends TestCase
{
    private function validProduct(): Product
    {
        $p = new Product();
        $p->setName('Mouse');
        $p->setPrice('10.00');
        $p->setDiscount('5');
        $p->setStock(10);
        $p->setStatus('active');
        return $p;
    }

    public function testValidProduct(): void
    {
        $m = new ProductManager();
        $this->assertTrue($m->validate($this->validProduct()));
    }

    public function testEmptyName(): void
    {
        $p = $this->validProduct();
        $p->setName('');
        $m = new ProductManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($p);
    }

    public function testNonPositivePrice(): void
    {
        $p = $this->validProduct();
        $p->setPrice('0.00');
        $m = new ProductManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($p);
    }

    public function testInvalidDiscount(): void
    {
        $p = $this->validProduct();
        $p->setDiscount('150');
        $m = new ProductManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($p);
    }

    public function testInvalidStatus(): void
    {
        $p = $this->validProduct();
        $p->setStatus('archived');
        $m = new ProductManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($p);
    }
}
