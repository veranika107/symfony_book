<?php

namespace App\Tests\Controller\Web\Comment;

use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CommentControllerTest extends WebTestCase
{
    public function testEdit(): void
    {
        $client = static::createClient();
        $container = self::getContainer();

        $user = $container->get(UserRepository::class)->findOneBy(['email' => 'mike@example.com']);
        $client->loginUser($user);

        $comment = $container->get(CommentRepository::class)->findOneBy(['email' => 'mike@example.com']);
        $oldCommentPhoto = $comment->getPhotoFilename();

        $client->request('GET', '/en/comment/' . $comment->getId() . '/edit');

        $client->submitForm('Submit', [
            'comment_form[text]' => 'Some feedback.',
            'comment_form[photo]' => dirname(__DIR__, 4).'/public/images/under-construction.gif',
        ]);

        $updatedComment = $container->get(CommentRepository::class)->findOneBy(['email' => 'mike@example.com']);

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSame('Some feedback.', $updatedComment->getText());
        $this->assertNotNull($updatedComment->getPhotoFilename());
        $this->assertNotSame($oldCommentPhoto, $updatedComment->getPhotoFilename());
    }

    public function testEditWithUnauthenticatedUser(): void
    {
        $client = static::createClient();
        $container = self::getContainer();
        $comment = $container->get(CommentRepository::class)->findOneBy(['email' => 'mike@example.com']);

        $client->request('GET', '/en/comment/' . $comment->getId() . '/edit');

        $this->assertResponseRedirects('/login');
    }

    public function testEditWithCommentNonOwner(): void
    {
        $client = static::createClient();
        $container = self::getContainer();

        $user = $container->get(UserRepository::class)->findOneBy(['email' => 'user@example.com']);
        $client->loginUser($user);

        $comment = $container->get(CommentRepository::class)->findOneBy(['email' => 'mike@example.com']);
        $client->request('GET', '/en/comment/' . $comment->getId() . '/edit');
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
