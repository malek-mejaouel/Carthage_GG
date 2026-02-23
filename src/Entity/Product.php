<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[\Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity(fields: ['sku'], message: 'This SKU is already in use.')]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: "Product name cannot be blank.")]
    #[Assert\Length(
        min: 3,
        max: 150,
        minMessage: "Product name must be at least {{ limit }} characters long.",
        maxMessage: "Product name cannot be longer than {{ limit }} characters."
    )]
    private string $name;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: "Price is required.")]
    #[Assert\Positive(message: "Price must be a positive number.")]
    private string $price;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Assert\PositiveOrZero(message: "Discount must be zero or positive.")]
    #[Assert\LessThanOrEqual(value: 100, message: "Discount cannot exceed 100%.")]
    private string $discount = '0';

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero(message: "Stock cannot be negative.")]
    private int $stock = 0;

    #[ORM\Column(length: 50, unique: true, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $sku = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $image = null;

    #[ORM\Column(type: 'decimal', precision: 2, scale: 1)]
    #[Assert\Range(min: 0, max: 5, notInRangeMessage: "Rating must be between {{ min }} and {{ max }}.")]
    private string $averageRating = '0';

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    private int $salesCount = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $isFeatured = false;

    #[ORM\Column(length: 10)]
    #[Assert\Choice(choices: ['active', 'inactive'], message: "Status must be either 'active' or 'inactive'.")]
    private string $status = 'active';

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getPrice(): string { return $this->price; }
    public function setPrice(string $price): self { $this->price = $price; return $this; }
    public function getDiscount(): string { return $this->discount; }
    public function setDiscount(string $discount): self { $this->discount = $discount; return $this; }
    public function getStock(): int { return $this->stock; }
    public function setStock(int $stock): self { $this->stock = $stock; return $this; }
    public function getSku(): ?string { return $this->sku; }
    public function setSku(?string $sku): self { $this->sku = $sku; return $this; }
    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): self { $this->image = $image; return $this; }
    public function getAverageRating(): string { return $this->averageRating; }
    public function setAverageRating(string $avg): self { $this->averageRating = $avg; return $this; }
    public function getSalesCount(): int { return $this->salesCount; }
    public function setSalesCount(int $count): self { $this->salesCount = $count; return $this; }
    public function isFeatured(): bool { return $this->isFeatured; }
    public function setIsFeatured(bool $f): self { $this->isFeatured = $f; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): self { $this->category = $category; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
    public function getFinalPrice(): float {
        $price = (float)$this->price;
        $discount = (float)$this->discount;
        $discount = max(0.0, min(100.0, $discount));
        $final = $price * (1 - ($discount / 100.0));
        if ($final < 0) { $final = 0.0; }
        return round($final, 2);
    }
}
