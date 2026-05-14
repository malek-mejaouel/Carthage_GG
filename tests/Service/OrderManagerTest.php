<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Order;
use App\Entity\User;
use App\Service\OrderManager;
use PHPUnit\Framework\TestCase;

class OrderManagerTest extends TestCase
{
    private function validOrder(): Order
    {
        $o = new Order();
        $o->setUser((new User())->setUsername('buyer'));
        $o->setCurrency('usd');
        $o->setAmount(10.0);
        $o->setStatus('paid');
        return $o;
    }

    public function testValidOrder(): void
    {
        $m = new OrderManager();
        $this->assertTrue($m->validate($this->validOrder()));
    }

    public function testMissingUser(): void
    {
        $o = new Order();
        $o->setCurrency('usd');
        $o->setAmount(10.0);
        $o->setStatus('paid');
        $m = new OrderManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($o);
    }

    public function testInvalidCurrency(): void
    {
        $o = $this->validOrder();
        $o->setCurrency('us');
        $m = new OrderManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($o);
    }

    public function testNonPositiveAmount(): void
    {
        $o = $this->validOrder();
        $o->setAmount(0.0);
        $m = new OrderManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($o);
    }

    public function testEmptyStatus(): void
    {
        $o = $this->validOrder();
        $o->setStatus('');
        $m = new OrderManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($o);
    }
}
