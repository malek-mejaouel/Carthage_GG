<?php

namespace App\Service;

use App\Entity\News;

class NewsManager
{
    public function validate(News $news): bool
    {
        $title = $news->getTitre();
        if (empty($title)) {
            throw new \InvalidArgumentException('Title is required');
        }
        if (strlen($title) < 3) {
            throw new \InvalidArgumentException('Title must be at least 3 characters');
        }
        $content = $news->getContenu();
        if (empty($content)) {
            throw new \InvalidArgumentException('Content is required');
        }
        if (strlen($content) < 10) {
            throw new \InvalidArgumentException('Content must be at least 10 characters');
        }
        $category = $news->getCategorie();
        if (empty($category)) {
            throw new \InvalidArgumentException('Category is required');
        }
        if (strlen($category) > 100) {
            throw new \InvalidArgumentException('Category must not exceed 100 characters');
        }
        $image = $news->getImage();
        if (empty($image)) {
            throw new \InvalidArgumentException('Image is required');
        }
        return true;
    }
}
