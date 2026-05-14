<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Event;
use App\Entity\Location;
use App\Service\EventManager;
use PHPUnit\Framework\TestCase;

class EventManagerTest extends TestCase
{
    public function testValidEvent(): void
    {
        $e = new Event();
        $e->setTitle('Lan Party');
        $e->setStartAt(new \DateTimeImmutable('+1 day'));
        $e->setLocation((new Location())->setName('Main Hall'));
        $e->setMaxSeats(100);
        $m = new EventManager();
        $this->assertTrue($m->validate($e));
    }

    public function testEmptyTitle(): void
    {
        $e = new Event();
        $e->setTitle('');
        $e->setStartAt(new \DateTimeImmutable('+1 day'));
        $e->setLocation((new Location())->setName('Main Hall'));
        $e->setMaxSeats(100);
        $m = new EventManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($e);
    }

    public function testMissingStartDate(): void
    {
        $e = new Event();
        $e->setTitle('Lan Party');
        $e->setLocation((new Location())->setName('Main Hall'));
        $e->setMaxSeats(100);
        $m = new EventManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($e);
    }

    public function testMissingLocation(): void
    {
        $e = new Event();
        $e->setTitle('Lan Party');
        $e->setStartAt(new \DateTimeImmutable('+1 day'));
        $e->setMaxSeats(100);
        $m = new EventManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($e);
    }

    public function testInvalidMaxSeats(): void
    {
        $e = new Event();
        $e->setTitle('Lan Party');
        $e->setStartAt(new \DateTimeImmutable('+1 day'));
        $e->setLocation((new Location())->setName('Main Hall'));
        $e->setMaxSeats(0);
        $m = new EventManager();
        $this->expectException(\InvalidArgumentException::class);
        $m->validate($e);
    }
}
