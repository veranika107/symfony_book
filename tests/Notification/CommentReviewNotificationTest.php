<?php

namespace App\Tests\Notification;

use App\Entity\Comment;
use App\Notification\CommentReviewNotification;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Channel\EmailChannel;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\NoRecipient;

class CommentReviewNotificationTest extends TestCase
{
    private function provideNotificationComments(): iterable
    {
        $comment = new Comment(author: 'Steve', text: 'This was awesome', email: 'steve@example.com');
        yield [['email', 'chat/slack'], $comment, Notification::IMPORTANCE_HIGH];

        $comment = new Comment(author: 'Tony', text: 'I am Iron Man', email: 'tony@example.com');
        yield [['email'], $comment, Notification::IMPORTANCE_LOW];
    }

    /**
     * @dataProvider provideNotificationComments
     */
    public function testGetChannels(array $expectedChannels, Comment $comment, string $importance): void
    {
        $notification = new CommentReviewNotification($comment, '/some-url');

        $channels = $notification->getChannels(new NoRecipient());

        $this->assertEqualsCanonicalizing($expectedChannels, $channels);
        $this->assertEquals($importance, $notification->getImportance());
    }
}
