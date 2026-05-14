<?php

namespace App\Service;

use App\Entity\Order;

class OrderManager
{
    public function validate(Order $order): bool
    {
        try {
            $user = $order->getUser();
        } catch (\Error $e) {
            $user = null;
        }
        if ($user === null) {
            throw new \InvalidArgumentException('User is required');
        }
        $currency = $order->getCurrency();
        if (empty($currency)) {
            throw new \InvalidArgumentException('Currency is required');
        }
        if (strlen($currency) !== 3) {
            throw new \InvalidArgumentException('Currency must be a 3-letter code');
        }
        $amount = $order->getAmount();
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }
        $status = $order->getStatus();
        if (empty($status)) {
            throw new \InvalidArgumentException('Status is required');
        }
        return true;
    }
}
