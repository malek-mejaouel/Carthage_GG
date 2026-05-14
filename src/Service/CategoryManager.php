<?php

namespace App\Service;

use App\Entity\Category;

class CategoryManager
{
    public function validate(Category $category): bool
    {
        $name = $category->getName();
        if (empty($name)) {
            throw new \InvalidArgumentException('Name is required');
        }
        if (strlen($name) < 2) {
            throw new \InvalidArgumentException('Name must be at least 2 characters');
        }
        if (strlen($name) > 100) {
            throw new \InvalidArgumentException('Name must not exceed 100 characters');
        }
        return true;
    }
}
