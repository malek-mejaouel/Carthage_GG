<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Commentaire as Comment;
use App\Entity\User;
use App\Service\CommentManager;
use PHPUnit\Framework\TestCase;

class CommentManagerTest extends TestCase
{
    public function testValidComment(): void
    {
        $comment = new Comment();
        $comment->setContenu('This is a valid comment text.');
        $user = new User();
        $user->setUsername('john');
        $comment->setUser($user);
        $manager = new CommentManager();
        $this->assertTrue($manager->validate($comment));
    }

    public function testEmptyContent(): void
    {
        $comment = new Comment();
        $comment->setContenu('');
        $user = new User();
        $user->setUsername('john');
        $comment->setUser($user);
        $manager = new CommentManager();
        $this->expectException(\InvalidArgumentException::class);
        $manager->validate($comment);
    }

    public function testShortContent(): void
    {
        $comment = new Comment();
        $comment->setContenu('abc');
        $user = new User();
        $user->setUsername('john');
        $comment->setUser($user);
        $manager = new CommentManager();
        $this->expectException(\InvalidArgumentException::class);
        $manager->validate($comment);
    }

    public function testMissingAuthorName(): void
    {
        $comment = new Comment();
        $comment->setContenu('Valid content for testing');
        $user = new User();
        $comment->setUser($user);
        $manager = new CommentManager();
        $this->expectException(\InvalidArgumentException::class);
        $manager->validate($comment);
    }

    public function testContentExceedsMaxLength(): void
    {
        $long = str_repeat('a', 501);
        $comment = new Comment();
        $comment->setContenu($long);
        $user = new User();
        $user->setUsername('john');
        $comment->setUser($user);
        $manager = new CommentManager();
        $this->expectException(\InvalidArgumentException::class);
        $manager->validate($comment);
    }
}
