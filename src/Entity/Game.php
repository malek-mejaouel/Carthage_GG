<?php
// src/Entity/Game.php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: 'games')]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $game_id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $genre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'game', targetEntity: Tournament::class)]
    private Collection $tournaments;

    #[ORM\OneToMany(mappedBy: 'game', targetEntity: GameMatch::class)]
    private Collection $matches;

    public function __construct()
    {
        $this->tournaments = new ArrayCollection();
        $this->matches = new ArrayCollection();
    }

    public function getGameId(): ?int
    {
        return $this->game_id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): self
    {
        $this->genre = $genre;
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

    public function getTournaments(): Collection
    {
        return $this->tournaments;
    }

    public function addTournament(Tournament $tournament): self
    {
        if (!$this->tournaments->contains($tournament)) {
            $this->tournaments->add($tournament);
            $tournament->setGame($this);
        }
        return $this;
    }

    public function removeTournament(Tournament $tournament): self
    {
        if ($this->tournaments->removeElement($tournament)) {
            if ($tournament->getGame() === $this) {
                $tournament->setGame(null);
            }
        }
        return $this;
    }

    public function getMatches(): Collection
    {
        return $this->matches;
    }

    public function addMatch(GameMatch $match): self
    {
        if (!$this->matches->contains($match)) {
            $this->matches->add($match);
            $match->setGame($this);
        }
        return $this;
    }

    public function removeMatch(GameMatch $match): self
    {
        if ($this->matches->removeElement($match)) {
            if ($match->getGame() === $this) {
                $match->setGame(null);
            }
        }
        return $this;
    }
}