<?php
// src/Entity/MatchPlayer.php

namespace App\Entity;

use App\Repository\MatchPlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MatchPlayerRepository::class)]
#[ORM\Table(name: 'match_players')]
class MatchPlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GameMatch::class, inversedBy: 'matchPlayers')]
    #[ORM\JoinColumn(name: 'match_id', referencedColumnName: 'match_id')]
    private ?GameMatch $match = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'matchPlayers')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'matchPlayers')]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'team_id')]
    private ?Team $team = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $role = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatch(): ?GameMatch
    {
        return $this->match;
    }

    public function setMatch(?GameMatch $match): self
    {
        $this->match = $match;
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

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        $this->team = $team;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;
        return $this;
    }
}