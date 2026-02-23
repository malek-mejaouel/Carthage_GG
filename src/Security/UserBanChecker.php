<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserBanChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof User && $user->isBanned()) {
            $remaining = $user->getRemainingBanTime();
            throw new CustomUserMessageAccountStatusException('Account temporarily suspended. Remaining time: ' . $remaining);
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // no-op
    }
}

