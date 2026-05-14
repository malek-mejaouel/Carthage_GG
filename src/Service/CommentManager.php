<?php

namespace App\Service;

use App\Entity\Commentaire as Comment;

class CommentManager
{
    public function validate(Comment $comment): bool
    {
        $content = $comment->getContenu();
        if (empty($content)) {
            throw new \InvalidArgumentException('Content is required');
        }
        if (strlen($content) < 5) {
            throw new \InvalidArgumentException('Content must be at least 5 characters');
        }
        if (strlen($content) > 500) {
            throw new \InvalidArgumentException('Content must not exceed 500 characters');
        }
        $authorName = $comment->getUser()?->getUsername();
        if (empty($authorName)) {
            throw new \InvalidArgumentException('Author name is required');
        }
        return true;
    }
}
