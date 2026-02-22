<?php
// src/Entity/TeamPlayer.php

namespace App\Entity;

use App\Repository\TeamPlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamPlayerRepository::class)]
#[ORM\Table(name: 'team_players')]
class TeamPlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'teamPlayers')]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'team_id')]
    private ?Team $team = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'teamPlayers')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }
}