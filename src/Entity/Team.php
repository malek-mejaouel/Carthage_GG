<?php
// src/Entity/Team.php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\Table(name: 'teams')]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $team_id = null;

    #[ORM\Column(length: 150)]
    private string $team_name = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'teams')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id')]
    private ?User $user = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $creation_date = null;

    /** @var Collection<int, TeamPlayer> */
    #[ORM\OneToMany(mappedBy: 'team', targetEntity: TeamPlayer::class)]
    private Collection $teamPlayers;

    /** @var Collection<int, GameMatch> */
    #[ORM\OneToMany(mappedBy: 'teamA', targetEntity: GameMatch::class)]
    private Collection $matchesAsTeamA;

    /** @var Collection<int, GameMatch> */
    #[ORM\OneToMany(mappedBy: 'teamB', targetEntity: GameMatch::class)]
    private Collection $matchesAsTeamB;

    /** @var Collection<int, MatchPlayer> */
    #[ORM\OneToMany(mappedBy: 'team', targetEntity: MatchPlayer::class)]
    private Collection $matchPlayers;

    public function __construct()
    {
        $this->teamPlayers = new ArrayCollection();
        $this->matchesAsTeamA = new ArrayCollection();
        $this->matchesAsTeamB = new ArrayCollection();
        $this->matchPlayers = new ArrayCollection();
    }

    public function getTeamId(): ?int
    {
        return $this->team_id;
    }

    public function getTeamName(): ?string
    {
        return $this->team_name;
    }

    public function setTeamName(string $team_name): self
    {
        $this->team_name = $team_name;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;
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

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creation_date;
    }

    public function setCreationDate(?\DateTimeInterface $creation_date): self
    {
        $this->creation_date = $creation_date;
        return $this;
    }

    /**
     * @return Collection<int, TeamPlayer>
     */
    public function getTeamPlayers(): Collection
    {
        return $this->teamPlayers;
    }

    public function addTeamPlayer(TeamPlayer $teamPlayer): self
    {
        if (!$this->teamPlayers->contains($teamPlayer)) {
            $this->teamPlayers->add($teamPlayer);
            $teamPlayer->setTeam($this);
        }
        return $this;
    }

    public function removeTeamPlayer(TeamPlayer $teamPlayer): self
    {
        if ($this->teamPlayers->removeElement($teamPlayer)) {
            if ($teamPlayer->getTeam() === $this) {
                $teamPlayer->setTeam(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, GameMatch>
     */
    public function getMatchesAsTeamA(): Collection
    {
        return $this->matchesAsTeamA;
    }

    /**
     * @return Collection<int, GameMatch>
     */
    public function getMatchesAsTeamB(): Collection
    {
        return $this->matchesAsTeamB;
    }

    /**
     * @return Collection<int, MatchPlayer>
     */
    public function getMatchPlayers(): Collection
    {
        return $this->matchPlayers;
    }
}
