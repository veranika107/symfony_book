<?php

namespace App\Tests\Entity;

use App\Entity\Comment;
use App\Entity\Conference;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class CommentTest extends TestCase
{
    public function testConstruct(): void
    {
        $comment = new Comment('Author', 'Text', 'email@example.com');
        $this->assertInstanceOf(Comment::class, $comment);
        $this->assertInstanceOf(UuidV7::class, $comment->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $comment->getCreatedAt());
        $this->assertNull($comment->getConference());
        $this->assertNull($comment->getPhotoFilename());
        $this->assertSame('submitted', $comment->getState());
    }

    public function testProperties(): void
    {
        $comment = new Comment('', '', '');
        $comment->setAuthor('Elza');
        $this->assertSame('Elza', $comment->getAuthor());

        $comment->setText('Like');
        $this->assertSame('Like', $comment->getText());

        $comment->setEmail('elza@example.com');
        $this->assertSame('elza@example.com', $comment->getEmail());

        $time = new \DateTimeImmutable('now');
        $comment->setCreatedAt($time);
        $this->assertSame($time, $comment->getCreatedAt());

        $conference = new Conference('', '' , false);
        $comment->setConference($conference);
        $this->assertSame($conference, $comment->getConference());

        $comment->setPhotoFilename('filename.png');
        $this->assertSame('filename.png', $comment->getPhotoFilename());

        $comment->setState('published');
        $this->assertSame('published', $comment->getState());
    }

    public function testToString(): void
    {
        $comment = new Comment('Jack', 'Dislike', 'jack@example.com');

        $this->assertEquals('jack@example.com', $comment);
    }
}
