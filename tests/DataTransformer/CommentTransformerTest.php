<?php

namespace App\Tests\DataTransformer;

use App\DataTransformer\CommentTransformer;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CommentTransformerTest extends KernelTestCase
{
    public function testTransformer(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $comment = $container->get(CommentRepository::class)->findOneBy(['email' => 'mike@example.com']);

        $photo = 'http://loclahost/uploads/photos/photo.png';
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getUriForPath')
            ->willReturn('http://loclahost/uploads/photos/photo.png');
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $expectedData = [
            'id' => $comment->getId(),
            'conference_id' => $comment->getConference()->getId(),
            'author' => $comment->getAuthor(),
            'email' => $comment->getEmail(),
            'text' => $comment->getText(),
            'photo' => $photo,
            'edited' => true,
        ];
        $commentTransformer = new CommentTransformer($requestStack);
        $data = $commentTransformer($comment);

        $this->assertSame($expectedData, $data);
    }
}
