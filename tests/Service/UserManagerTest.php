<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    /**
     * Valid user passes all rules:
     * - first name non-empty
     * - valid email format
     * - password length >= 8
     */
    public function testValidUser(): void
    {
        $user = new User();
        $user->setEmail('john.doe@example.com');
        $user->setPassword('password123');
        $user->setFirstName('John');
        $manager = new UserManager();
        $this->assertTrue($manager->validate($user));
    }

    /**
     * Missing first name should trigger \InvalidArgumentException.
     */
    public function testUserWithoutFirstName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $user = new User();
        $user->setEmail('john.doe@example.com');
        $user->setPassword('password123');
        $user->setFirstName('');
        $manager = new UserManager();
        $manager->validate($user);
    }

    /**
     * Invalid email format should trigger \InvalidArgumentException.
     */
    public function testUserWithInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $user = new User();
        $user->setEmail('invalid-email');
        $user->setPassword('password123');
        $user->setFirstName('John');
        $manager = new UserManager();
        $manager->validate($user);
    }

    /**
     * Password shorter than 8 characters should trigger \InvalidArgumentException.
     */
    public function testUserWithShortPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $user = new User();
        $user->setEmail('john.doe@example.com');
        $user->setPassword('short');
        $user->setFirstName('John');
        $manager = new UserManager();
        $manager->validate($user);
    }
}
