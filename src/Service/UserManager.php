<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\User;

class UserManager
{
    /**
     * Validate business rules for User entity.
     *
     * - First name is required (uses empty()).
     * - Email is required (uses empty()) and must be valid (FILTER_VALIDATE_EMAIL).
     * - Password must have at least 8 characters.
     *
     * @throws \InvalidArgumentException If any validation rule fails.
     */
    public function validate(User $user): bool
    {
        $email = (string) $user->getEmail();
        $password = (string) $user->getPassword();
        $firstName = (string) $user->getFirstName();
        // First name required
        if (empty($firstName)) {
            throw new \InvalidArgumentException('First name is required');
        }
        // Email required
        if (empty($email)) {
            throw new \InvalidArgumentException('Email is required');
        }
        // Email must have valid format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }
        // Password minimum length: 8 characters
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Password too short');
        }
        return true;
    }
}
