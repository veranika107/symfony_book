<?php

namespace App\Tests\MessageHandler;

use App\Entity\Comment;
use App\Message\CommentMessage;
use App\MessageHandler\CommentMessageHandler;
use App\Repository\CommentRepository;
use App\Service\ImageOptimizer;
use App\Service\SpamChecker;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommentMessageHandlerTest extends KernelTestCase
{
    private Comment $comment;

    private CommentMessage $commentMessage;

    private CommentRepository $commentRepository;

    private ImageOptimizer $imageOptimizer;

    private CommentMessageHandler $commentMessageHandler;

    protected function setUp(): void
    {
        $this->comment = new Comment('Fred', 'Awesome', 'fred@example.com');

        $this->commentMessage = new CommentMessage($this->comment->getId(), '/review-url');

        self::bootKernel();
        $container = static::getContainer();

        $this->commentRepository = $this->createMock(CommentRepository::class);
        $this->commentRepository->method('save');
        $container->set(CommentRepository::class, $this->commentRepository);

        $spamChecker = $this->createMock(SpamChecker::class);
        $spamChecker->method('getSpamScore')
            ->willReturn(2);
        $container->set(SpamChecker::class, $spamChecker);

        $this->imageOptimizer = $this->createMock(ImageOptimizer::class);
        $container->set(ImageOptimizer::class, $this->imageOptimizer);

        $this->commentMessageHandler = $container->get(CommentMessageHandler::class);
    }

    public function testCommentNotNull(): void
    {
        $this->commentRepository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        try {
            ($this->commentMessageHandler)($this->commentMessage);
        } catch (\Error $e) {
            $this->fail('Comment should not be null');
        }
    }

    private function provideTransition(): iterable
    {
        yield 'accept transition' => ['submitted', 'spam'];

        yield 'optimize transition' => ['ready', 'published'];
    }

    /**
     * @dataProvider provideTransition
     */
    public function testTransitions(string $initialState, string $finalState): void
    {
        $this->comment->setState($initialState);

        $this->commentRepository->expects($this->atLeastOnce())
            ->method('find')
            ->willReturn($this->comment);
        $this->commentRepository->expects($this->atLeastOnce())
            ->method('save');

        ($this->commentMessageHandler)($this->commentMessage);

        $commentState = $this->comment->getState();
        $this->assertSame($finalState, $commentState);
    }

    public function testSendingNotification(): void
    {
        $this->comment->setState('potential_spam');

        $this->commentRepository->expects($this->once())
            ->method('find')
            ->willReturn($this->comment);

        ($this->commentMessageHandler)($this->commentMessage);

        $this->assertNotificationCount(1);
    }

    public function testCallingImageResizing(): void
    {
        $this->comment->setState('ready');
        $this->comment->setPhotoFilename('filename.png');

        $this->commentRepository->expects($this->once())
            ->method('find')
            ->willReturn($this->comment);

        $this->imageOptimizer->expects($this->once())
            ->method('resize');

        ($this->commentMessageHandler)($this->commentMessage);
    }
}
