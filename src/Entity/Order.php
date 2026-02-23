<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'order_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 3)]
    private string $currency = 'usd';

    #[ORM\Column(type: 'float')]
    private float $amount = 0.0;

    #[ORM\Column(type: 'json')]
    private array $items = [];

    #[ORM\Column(length: 20)]
    private string $shippingMethod = 'standard';

    #[ORM\Column(type: 'float')]
    private float $shipping = 0.0;

    #[ORM\Column(type: 'float')]
    private float $tax = 0.0;

    #[ORM\Column(length: 50)]
    private string $status = 'paid';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getCurrency(): string { return $this->currency; }
    public function setCurrency(string $currency): self { $this->currency = $currency; return $this; }
    public function getAmount(): float { return $this->amount; }
    public function setAmount(float $amount): self { $this->amount = $amount; return $this; }
    public function getItems(): array { return $this->items; }
    public function setItems(array $items): self { $this->items = $items; return $this; }
    public function getShippingMethod(): string { return $this->shippingMethod; }
    public function setShippingMethod(string $shippingMethod): self { $this->shippingMethod = $shippingMethod; return $this; }
    public function getShipping(): float { return $this->shipping; }
    public function setShipping(float $shipping): self { $this->shipping = $shipping; return $this; }
    public function getTax(): float { return $this->tax; }
    public function setTax(float $tax): self { $this->tax = $tax; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getStripeSessionId(): ?string { return $this->stripeSessionId; }
    public function setStripeSessionId(?string $stripeSessionId): self { $this->stripeSessionId = $stripeSessionId; return $this; }
    public function getStripePaymentIntentId(): ?string { return $this->stripePaymentIntentId; }
    public function setStripePaymentIntentId(?string $stripePaymentIntentId): self { $this->stripePaymentIntentId = $stripePaymentIntentId; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
