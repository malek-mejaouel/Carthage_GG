<?php

namespace App\Entity;

use App\Repository\StreamRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StreamRepository::class)]
#[ORM\Table(name: 'streams')]
class Stream
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'stream_id', type: 'integer')]
    private ?int $stream_id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $platform; // 'twitch' | 'youtube'

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $channel_name = null; // Twitch

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $youtube_video_id = null; // YouTube

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $thumbnail = null;

    #[ORM\Column(type: 'boolean')]
    private bool $is_live = false;

    #[ORM\Column(type: 'integer')]
    private int $viewer_count = 0;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $created_by = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $created_at;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updated_at;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->created_at = $now;
        $this->updated_at = $now;
    }

    public function getStreamId(): ?int { return $this->stream_id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getPlatform(): string { return $this->platform; }
    public function setPlatform(string $platform): self { $this->platform = $platform; return $this; }

    public function getChannelName(): ?string { return $this->channel_name; }
    public function setChannelName(?string $channel_name): self { $this->channel_name = $channel_name; return $this; }

    public function getYoutubeVideoId(): ?string { return $this->youtube_video_id; }
    public function setYoutubeVideoId(?string $youtube_video_id): self { $this->youtube_video_id = $youtube_video_id; return $this; }

    public function getThumbnail(): ?string { return $this->thumbnail; }
    public function setThumbnail(?string $thumbnail): self { $this->thumbnail = $thumbnail; return $this; }

    public function isLive(): bool { return $this->is_live; }
    public function setIsLive(bool $is_live): self { $this->is_live = $is_live; return $this; }

    public function getViewerCount(): int { return $this->viewer_count; }
    public function setViewerCount(int $viewer_count): self { $this->viewer_count = $viewer_count; return $this; }

    public function getCreatedBy(): ?User { return $this->created_by; }
    public function setCreatedBy(?User $created_by): self { $this->created_by = $created_by; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->created_at; }
    public function setCreatedAt(\DateTimeImmutable $created_at): self { $this->created_at = $created_at; return $this; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updated_at; }
    public function setUpdatedAt(\DateTimeImmutable $updated_at): self { $this->updated_at = $updated_at; return $this; }
}
