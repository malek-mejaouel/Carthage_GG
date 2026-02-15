<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Commentaire Entity (Comment)
 * 
 * This entity represents a user comment on a news article.
 * Comments can have nested replies via parent_id encoding in the content.
 * Users can upvote and downvote comments, and comments can include GIF media.
 */
#[ORM\Entity]
#[ORM\Table(name: "commentaires")]
class Commentaire
{
    // ==================== DATABASE COLUMNS ====================
    
    /**
     * Unique identifier for the comment
     * Auto-generated primary key in the database
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"commentaire_id", type:"integer")]
    private ?int $commentaire_id = null;

    /**
     * The comment text content
     * Supports very long text (TEXT type in database)
     * May contain encoded parent_id for nested comments: "__PARENT__{id}||{actual_comment}"
     * Also supports emoji and text mixing
     */
    #[ORM\Column(type:"text")]
    private ?string $contenu = null;

    /**
     * Date and time when the comment was created
     * Automatically set to current time when a comment is added
     * Format: DateTime object (e.g., 2026-02-06 15:30:00)
     */
    #[ORM\Column(type:"datetime")]
    private ?\DateTimeInterface $date_commentaire = null;

    /**
     * URL to an optional GIF image
     * Can be null if no GIF was selected
     * This allows users to express reactions with GIFs
     * Maximum length: 255 characters (URL)
     */
    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $gif_url = null;

    /**
     * Number of upvotes this comment has received
     * Default: 0
     * Each user can upvote once (tracked client-side with localStorage)
     */
    #[ORM\Column(type:"integer", options:["default"=>0])]
    private int $upvotes = 0;

    /**
     * Number of downvotes this comment has received
     * Default: 0
     * Each user can downvote once (tracked client-side with localStorage)
     */
    #[ORM\Column(type:"integer", options:["default"=>0])]
    private int $downvotes = 0;

    // ==================== DATABASE RELATIONSHIPS ====================
    
    /**
     * The news article this comment belongs to
     * Many comments can belong to one news article (Many-to-One relationship)
     * When a news article is deleted, cascading will remove all its comments
     */
    #[ORM\ManyToOne(targetEntity: News::class)]
    #[ORM\JoinColumn(name:"news_id", referencedColumnName:"news_id", nullable:false)]
    private ?News $news = null;

    /**
     * COMMENTED OUT: User relationship
     * Can be uncommented when User entity is properly implemented
     * Would link each comment to the user who created it
     */
    // #[ORM\ManyToOne(targetEntity: User::class)]
    // #[ORM\JoinColumn(name:"user_id", referencedColumnName:"user_id", nullable:false)]
    // private ?User $user = null;

    // ==================== GETTER METHODS ====================
    
    /**
     * Get the unique ID of this comment
     * 
     * @return ?int The comment ID or null if not yet persisted
     */
    public function getCommentaireId(): ?int
    {
        return $this->commentaire_id;
    }

    /**
     * Get the comment text content
     * Note: May contain parent_id encoding that needs to be parsed
     * 
     * @return ?string The comment content or null
     */
    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    // ==================== SETTER METHODS ====================
    
    /**
     * Set the comment text content
     * Fluent interface: returns $this to allow method chaining
     * 
     * @param string $contenu The comment text (can include emojis and text)
     * @return self Returns this object for method chaining
     */
    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;
        return $this;
    }

    /**
     * Get the date and time when this comment was created
     * 
     * @return ?\DateTimeInterface The creation datetime or null
     */
    public function getDateCommentaire(): ?\DateTimeInterface
    {
        return $this->date_commentaire;
    }

    /**
     * Set the date and time for this comment
     * Called automatically when the comment is created
     * 
     * @param \DateTimeInterface $date The date and time
     * @return self Returns this object for method chaining
     */
    public function setDateCommentaire(\DateTimeInterface $date): self
    {
        $this->date_commentaire = $date;
        return $this;
    }

    /**
     * Get the GIF URL associated with this comment
     * May be null if no GIF was included
     * 
     * @return ?string The GIF URL or null
     */
    public function getGifUrl(): ?string
    {
        return $this->gif_url;
    }

    /**
     * Set the GIF URL for this comment
     * 
     * @param ?string $gif The GIF URL (can be null)
     * @return self Returns this object for method chaining
     */
    public function setGifUrl(?string $gif): self
    {
        $this->gif_url = $gif;
        return $this;
    }

    /**
     * Get the number of upvotes this comment has
     * 
     * @return int The upvote count (0 or more)
     */
    public function getUpvotes(): int
    {
        return $this->upvotes;
    }

    /**
     * Set the upvote count for this comment
     * 
     * @param int $value The new upvote count
     * @return self Returns this object for method chaining
     */
    public function setUpvotes(int $value): self
    {
        $this->upvotes = $value;
        return $this;
    }

    /**
     * Get the number of downvotes this comment has
     * 
     * @return int The downvote count (0 or more)
     */
    public function getDownvotes(): int
    {
        return $this->downvotes;
    }

    /**
     * Set the downvote count for this comment
     * 
     * @param int $value The new downvote count
     * @return self Returns this object for method chaining
     */
    public function setDownvotes(int $value): self
    {
        $this->downvotes = $value;
        return $this;
    }

    /**
     * Get the news article this comment belongs to
     * 
     * @return ?News The news article object or null
     */
    public function getNews(): ?News
    {
        return $this->news;
    }

    /**
     * Set the news article this comment belongs to
     * 
     * @param ?News $news The news article this comment is for
     * @return self Returns this object for method chaining
     */
    public function setNews(?News $news): self
    {
        $this->news = $news;
        return $this;
    }

    /**
     * Get the user who created this comment
     * Currently not fully implemented
     * 
     * @return ?User The user object or null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set the user who created this comment
     * To be used when User entity is properly implemented
     * 
     * @param ?User $user The user who created this comment
     * @return self Returns this object for method chaining
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }
}
