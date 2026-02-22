<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * News Entity
 * 
 * This entity represents a news article in the CarthageGG platform.
 * Each news article can be displayed on the website and users can comment on it.
 * News articles can be categorized and contain an image along with detailed content.
 */
#[ORM\Entity]
#[ORM\Table(name: "news")]
class News
{
    // ==================== DATABASE COLUMNS ====================
    
    /**
     * Unique identifier for the news article
     * Auto-generated primary key in the database
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"news_id", type:"integer")]
    private ?int $news_id = null;

    /**
     * Title of the news article
     * Maximum length: 255 characters
     * Example: "New Tournament Announced"
     */
    #[ORM\Column(type:"string", length:255)]
    private ?string $titre = null;

    /**
     * Main content/body of the news article
     * Can be a very long text (stored as TEXT in database)
     * Supports multiple paragraphs and detailed information
     */
    #[ORM\Column(type:"text")]
    private ?string $contenu = null;

    /**
     * Filename of the featured image for this news article
     * Stored as the filename only (e.g., "news-image-123.jpg")
     * Full path: /public/uploads/news/{image}
     */
    #[ORM\Column(type:"string", length:255)]
    private ?string $image = null;

    /**
     * Category of the news article
     * Examples: "Tournament", "Match Update", "Team News", "Player News", etc.
     * Maximum length: 100 characters
     */
    #[ORM\Column(type:"string", length:100)]
    private ?string $categorie = null;

    /**
     * Publication date and time of the news article
     * Automatically set when the news is created
     * Format: DateTime object (e.g., 2026-02-06 15:30:00)
     */
    #[ORM\Column(type:"datetime")]
    private ?\DateTimeInterface $date_publication = null;

    // ==================== GETTER METHODS ====================
    
    /**
     * Get the unique ID of this news article
     * 
     * @return ?int The news ID or null if not yet persisted to database
     */
    public function getNewsId(): ?int
    {
        return $this->news_id;
    }

    /**
     * Get the title of this news article
     * 
     * @return ?string The title or null
     */
    public function getTitre(): ?string
    {
        return $this->titre;
    }

    // ==================== SETTER METHODS ====================
    
    /**
     * Set the title of this news article
     * Fluent interface: returns $this to allow method chaining
     * Example: $news->setTitre("New Event")->setCategorie("Event");
     * 
     * @param string $titre The new title
     * @return self Returns this object for method chaining
     */
    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    /**
     * Get the content/body of this news article
     * 
     * @return ?string The full content text or null
     */
    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    /**
     * Set the content/body of this news article
     * 
     * @param string $contenu The article content
     * @return self Returns this object for method chaining
     */
    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;
        return $this;
    }

    /**
     * Get the image filename for this news article
     * 
     * @return ?string The image filename or null
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * Set the image filename for this news article
     * Should include just the filename, not the full path
     * 
     * @param string $image The image filename
     * @return self Returns this object for method chaining
     */
    public function setImage(string $image): self
    {
        $this->image = $image;
        return $this;
    }

    /**
     * Get the category of this news article
     * 
     * @return ?string The category name or null
     */
    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    /**
     * Set the category of this news article
     * 
     * @param string $categorie The category name (e.g., "Tournament")
     * @return self Returns this object for method chaining
     */
    public function setCategorie(string $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    /**
     * Get the publication date and time of this news article
     * 
     * @return ?\DateTimeInterface The publication DateTime or null
     */
    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->date_publication;
    }

    /**
     * Set the publication date and time of this news article
     * Usually called automatically when creating new news, but can be modified later
     * 
     * @param \DateTimeInterface $date The publication date and time
     * @return self Returns this object for method chaining
     */
    public function setDatePublication(\DateTimeInterface $date): self
    {
        $this->date_publication = $date;
        return $this;
    }
}
