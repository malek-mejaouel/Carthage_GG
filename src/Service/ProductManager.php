<?php

namespace App\Service;

use App\Entity\Product;

class ProductManager
{
    public function validate(Product $product): bool
    {
        $name = $product->getName();
        if (empty($name)) {
            throw new \InvalidArgumentException('Name is required');
        }
        if (strlen($name) < 3) {
            throw new \InvalidArgumentException('Name must be at least 3 characters');
        }
        $price = (float)$product->getPrice();
        if ($price <= 0) {
            throw new \InvalidArgumentException('Price must be positive');
        }
        $discount = (float)$product->getDiscount();
        if ($discount < 0 || $discount > 100) {
            throw new \InvalidArgumentException('Discount must be between 0 and 100');
        }
        $stock = $product->getStock();
        if ($stock < 0) {
            throw new \InvalidArgumentException('Stock cannot be negative');
        }
        $status = $product->getStatus();
        if (empty($status)) {
            throw new \InvalidArgumentException('Status is required');
        }
        if ($status !== 'active' && $status !== 'inactive') {
            throw new \InvalidArgumentException('Status must be active or inactive');
        }
        return true;
    }
}
