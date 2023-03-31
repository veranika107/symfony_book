<?php

namespace App\Tests\Message;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Message\CommentMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class CommentMessageTest extends TestCase
{
    public function testProperties(): void
    {
        $uuid = UuidV7::v7();
        $commentMessage = new CommentMessage($uuid, '/url');
        $this->assertInstanceOf(CommentMessage::class, $commentMessage);

        $this->assertEquals($uuid, $commentMessage->getId());
        $this->assertEquals('/url', $commentMessage->getReviewUrl());
        $this->assertEquals([], $commentMessage->getContext());
    }
}
