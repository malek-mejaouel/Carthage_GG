<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $user_id = null;

    #[ORM\Column(length: 100)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 150, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $roles = '';

    #[ORM\Column(name: 'first_name', length: 100, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(name: 'last_name', length: 100, nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private ?bool $is_active = true;

    #[ORM\Column(name: 'last_login_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $last_login_at = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Team::class)]
    private Collection $teams;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Tournament::class)]
    private Collection $tournaments;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: TeamPlayer::class)]
    private Collection $teamPlayers;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: MatchPlayer::class)]
    private Collection $matchPlayers;

    public function __construct()
    {
        $this->teams = new ArrayCollection();
        $this->tournaments = new ArrayCollection();
        $this->teamPlayers = new ArrayCollection();
        $this->matchPlayers = new ArrayCollection();
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getRoles(): ?string
    {
        return $this->roles;
    }

    public function setRoles(string $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->is_active = $isActive;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->last_login_at;
    }

    public function setLastLoginAt(?\DateTimeInterface $dt): self
    {
        $this->last_login_at = $dt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $dt): self
    {
        $this->created_at = $dt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $dt): self
    {
        $this->updated_at = $dt;
        return $this;
    }

    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): self
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
            $team->setUser($this);
        }
        return $this;
    }

    public function removeTeam(Team $team): self
    {
        if ($this->teams->removeElement($team)) {
            if ($team->getUser() === $this) {
                $team->setUser(null);
            }
        }
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
            $tournament->setUser($this);
        }
        return $this;
    }

    public function removeTournament(Tournament $tournament): self
    {
        if ($this->tournaments->removeElement($tournament)) {
            if ($tournament->getUser() === $this) {
                $tournament->setUser(null);
            }
        }
        return $this;
    }

    public function getTeamPlayers(): Collection
    {
        return $this->teamPlayers;
    }

    public function addTeamPlayer(TeamPlayer $teamPlayer): self
    {
        if (!$this->teamPlayers->contains($teamPlayer)) {
            $this->teamPlayers->add($teamPlayer);
            $teamPlayer->setUser($this);
        }
        return $this;
    }

    public function removeTeamPlayer(TeamPlayer $teamPlayer): self
    {
        if ($this->teamPlayers->removeElement($teamPlayer)) {
            if ($teamPlayer->getUser() === $this) {
                $teamPlayer->setUser(null);
            }
        }
        return $this;
    }

    public function getMatchPlayers(): Collection
    {
        return $this->matchPlayers;
    }

    public function addMatchPlayer(MatchPlayer $matchPlayer): self
    {
        if (!$this->matchPlayers->contains($matchPlayer)) {
            $this->matchPlayers->add($matchPlayer);
            $matchPlayer->setUser($this);
        }
        return $this;
    }

    public function removeMatchPlayer(MatchPlayer $matchPlayer): self
    {
        if ($this->matchPlayers->removeElement($matchPlayer)) {
            if ($matchPlayer->getUser() === $this) {
                $matchPlayer->setUser(null);
            }
        }
        return $this;
    }
}
