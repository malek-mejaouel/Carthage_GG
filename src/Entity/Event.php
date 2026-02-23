<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use App\State\EventProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Reservation as ReservationEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
#[ApiResource(
    operations: [
        new Get(),
        new Post(processor: EventProcessor::class),
    ]
)]
#[ORM\Entity]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endAt = null;

    // 🔥 Ajout capacité max (important pour réservation avancée)
    #[ORM\Column(type: 'integer')]
    private int $maxSeats = 100;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'events')]
    private ?Location $location = null;

    #[ORM\OneToMany(
        mappedBy: 'event',
        targetEntity: Reservation::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    // ---------------- GETTERS & SETTERS ----------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): self
    {
        $this->startAt = $startAt;
        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeImmutable $endAt): self
    {
        $this->endAt = $endAt;
        return $this;
    }

    public function getMaxSeats(): int
    {
        return $this->maxSeats;
    }

    public function setMaxSeats(int $maxSeats): self
    {
        $this->maxSeats = $maxSeats;
        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setEvent($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getEvent() === $this) {
                $reservation->setEvent(null);
            }
        }
        return $this;
    }

    // 🔥 MÉTIER AVANCÉ : calcul des places réservées
    public function getReservedSeats(): int
    {
        $sum = 0;
        foreach ($this->reservations as $r) {
            // Count only confirmed reservations towards occupied seats
            if (method_exists($r, 'getStatus') && $r->getStatus() === ReservationEntity::STATUS_CONFIRMED) {
                $sum += $r->getSeats();
            }
        }
        return $sum;
    }

    // 🔥 MÉTIER AVANCÉ : places restantes
    public function getAvailableSeats(): int
    {
        $reserved = $this->getReservedSeats();

        // Determine effective capacity: min(event maxSeats, location capacity if set)
        $effectiveCapacity = $this->maxSeats;
        if ($this->location && method_exists($this->location, 'getCapacity')) {
            $locCap = $this->location->getCapacity();
            if ($locCap !== null) {
                $effectiveCapacity = min($this->maxSeats, $locCap);
            }
        }

        $available = $effectiveCapacity - $reserved;
        return $available > 0 ? $available : 0;
    }
}