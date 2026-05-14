<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Location;
use App\Service\LocationManager;
use PHPUnit\Framework\TestCase;

class LocationManagerTest extends TestCase
{
    public function testValidLocation(): void
    {
        $l = new Location();
        $l->setName('Arena')->setCapacity(500);
        $m = new LocationManager();
        $this->assertTrue($m->validate($l));
    }

    public function testEmptyName(): void
    {
        $l = new Location();
        $l->setName('');
        $m = new LocationManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($l);
    }

    public function testShortName(): void
    {
        $l = new Location();
        $l->setName('AB');
        $m = new LocationManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($l);
    }

    public function testLongAddress(): void
    {
        $l = new Location();
        $l->setName('Arena')->setAddress(str_repeat('a', 501));
        $m = new LocationManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($l);
    }

    public function testNegativeCapacity(): void
    {
        $l = new Location();
        $l->setName('Arena')->setCapacity(-5);
        $m = new LocationManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($l);
    }
}
