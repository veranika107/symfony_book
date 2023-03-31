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
        $this->assertEquals(null, $comment->getConference());
        $this->assertEquals(null, $comment->getPhotoFilename());
        $this->assertEquals('submitted', $comment->getState());
    }

    public function testProperties(): void
    {
        $comment = new Comment('', '', '');
        $comment->setAuthor('Elza');
        $this->assertEquals('Elza', $comment->getAuthor());

        $comment->setText('Like');
        $this->assertEquals('Like', $comment->getText());

        $comment->setEmail('elza@example.com');
        $this->assertEquals('elza@example.com', $comment->getEmail());

        $time = new \DateTimeImmutable('now');
        $comment->setCreatedAt($time);
        $this->assertEquals($time, $comment->getCreatedAt());

        $conference = new Conference('', '' , false);
        $comment->setConference($conference);
        $this->assertEquals($conference, $comment->getConference());

        $comment->setPhotoFilename('filename.png');
        $this->assertEquals('filename.png', $comment->getPhotoFilename());

        $comment->setState('published');
        $this->assertEquals('published', $comment->getState());
    }
}
