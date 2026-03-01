<?php
// src/Entity/Tournament.php

namespace App\Entity;

use App\Repository\TournamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
#[ORM\Table(name: 'tournaments')]
class Tournament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $tournament_id = null;

    #[ORM\Column(length: 150)]
    private string $tournament_name = '';

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'tournaments')]
    #[ORM\JoinColumn(name: 'game_id', referencedColumnName: 'game_id')]
    private ?Game $game = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $end_date = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tournaments')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $prize_pool = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $location = null;

    /** @var Collection<int, GameMatch> */
    #[ORM\OneToMany(mappedBy: 'tournament', targetEntity: GameMatch::class)]
    private Collection $matches;

    public function __construct()
    {
        $this->matches = new ArrayCollection();
    }

    public function getTournamentId(): ?int
    {
        return $this->tournament_id;
    }

    public function getTournamentName(): ?string
    {
        return $this->tournament_name;
    }

    public function setTournamentName(string $tournament_name): self
    {
        $this->tournament_name = $tournament_name;
        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): self
    {
        $this->game = $game;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate(?\DateTimeInterface $start_date): self
    {
        $this->start_date = $start_date;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEndDate(?\DateTimeInterface $end_date): self
    {
        $this->end_date = $end_date;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getPrizePool(): ?string
    {
        return $this->prize_pool;
    }

    public function setPrizePool(?string $prize_pool): self
    {
        $this->prize_pool = $prize_pool;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return Collection<int, GameMatch>
     */
    public function getMatches(): Collection
    {
        return $this->matches;
    }

    public function addMatch(GameMatch $match): self
    {
        if (!$this->matches->contains($match)) {
            $this->matches->add($match);
            $match->setTournament($this);
        }
        return $this;
    }

    public function removeMatch(GameMatch $match): self
    {
        if ($this->matches->removeElement($match)) {
            if ($match->getTournament() === $this) {
                $match->setTournament(null);
            }
        }
        return $this;
    }

    /**
     * Count the number of distinct teams participating in this tournament.
     *
     * @return int
     */
    public function getTeamCount(): int
    {
        $teams = [];
        foreach ($this->matches as $match) {
            if ($match->getTeamA()) {
                $teams[$match->getTeamA()->getTeamId()] = true;
            }
            if ($match->getTeamB()) {
                $teams[$match->getTeamB()->getTeamId()] = true;
            }
        }
        return count($teams);
    }
}
