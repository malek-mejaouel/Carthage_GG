<?php
// src/Entity/GameMatch.php

namespace App\Entity;

use App\Repository\MatchRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Game;

#[ORM\Entity(repositoryClass: MatchRepository::class)]
#[ORM\Table(name: 'matches')]
class GameMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $match_id = null;

    #[ORM\ManyToOne(targetEntity: Tournament::class, inversedBy: 'matches')]
    #[ORM\JoinColumn(name: 'tournament_id', referencedColumnName: 'tournament_id', nullable: true)]
    private ?Tournament $tournament = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'matchesAsTeamA')]
    #[ORM\JoinColumn(name: 'team_a_id', referencedColumnName: 'team_id', nullable: true)]
    private ?Team $teamA = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'matchesAsTeamB')]
    #[ORM\JoinColumn(name: 'team_b_id', referencedColumnName: 'team_id', nullable: true)]
    private ?Team $teamB = null;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'matches')]
    #[ORM\JoinColumn(name: 'game_id', referencedColumnName: 'game_id', nullable: true)]
    private ?Game $game = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $match_date = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $score_team_a = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $score_team_b = null;

    public function getMatchId(): ?int
    {
        return $this->match_id;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function setTournament(?Tournament $tournament): self
    {
        $this->tournament = $tournament;
        return $this;
    }

    public function getTeamA(): ?Team
    {
        return $this->teamA;
    }

    public function setTeamA(?Team $teamA): self
    {
        $this->teamA = $teamA;
        return $this;
    }

    public function getTeamB(): ?Team
    {
        return $this->teamB;
    }

    public function setTeamB(?Team $teamB): self
    {
        $this->teamB = $teamB;
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

    public function getMatchDate(): ?\DateTimeInterface
    {
        return $this->match_date;
    }

    public function setMatchDate(?\DateTimeInterface $match_date): self
    {
        $this->match_date = $match_date;
        return $this;
    }

    public function getScoreTeamA(): ?int
    {
        return $this->score_team_a;
    }

    public function setScoreTeamA(?int $score_team_a): self
    {
        $this->score_team_a = $score_team_a;
        return $this;
    }

    public function getScoreTeamB(): ?int
    {
        return $this->score_team_b;
    }

    public function setScoreTeamB(?int $score_team_b): self
    {
        $this->score_team_b = $score_team_b;
        return $this;
    }
}
