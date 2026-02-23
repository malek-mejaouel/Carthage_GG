<?php

namespace App\Entity;

use App\Repository\StreamMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StreamMessageRepository::class)]
#[ORM\Table(name: 'stream_messages')]
class StreamMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'message_id', type: 'integer')]
    private ?int $message_id = null;

    #[ORM\ManyToOne(targetEntity: Stream::class)]
    #[ORM\JoinColumn(name: 'stream_id', referencedColumnName: 'stream_id', nullable: false, onDelete: 'CASCADE')]
    private ?Stream $stream = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $message;

    #[ORM\Column(type: 'boolean')]
    private bool $is_deleted = false;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getMessageId(): ?int { return $this->message_id; }

    public function getStream(): ?Stream { return $this->stream; }
    public function setStream(?Stream $stream): self { $this->stream = $stream; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }

    public function isDeleted(): bool { return $this->is_deleted; }
    public function setIsDeleted(bool $deleted): self { $this->is_deleted = $deleted; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->created_at; }
    public function setCreatedAt(\DateTimeInterface $created_at): self { $this->created_at = $created_at; return $this; }
}

