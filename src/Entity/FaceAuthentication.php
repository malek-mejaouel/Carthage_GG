<?php

namespace App\Entity;

use App\Repository\FaceAuthenticationRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

#[ORM\Entity(repositoryClass: FaceAuthenticationRepository::class)]
#[ORM\Table(name: 'face_authentication')]
class FaceAuthentication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'face_id')]
    private ?int $id = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descriptor = null;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = false;

    #[ORM\Column(name: 'registered_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $registeredAt = null;

    #[ORM\OneToOne(inversedBy: 'faceAuthentication', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescriptor(): ?string
    {
        return $this->descriptor;
    }

    public function setDescriptor(?string $descriptor): static
    {
        $this->descriptor = $descriptor;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getRegisteredAt(): ?\DateTimeInterface
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(?\DateTimeInterface $registeredAt): static
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
}

