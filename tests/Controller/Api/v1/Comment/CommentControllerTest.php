<?php

namespace App\Tests\Controller\Api\v1\Comment;

use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class CommentControllerTest extends WebTestCase
{
    /**
     * @beforeClass
     */
    public static function prepareImage(): void
    {
        $upload_dir = dirname(__DIR__, 5) . '/public/uploads/photos';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $photoDir = $upload_dir . '/test_image.gif';
        copy(dirname(__DIR__, 5) . '/public/images/under-construction.gif', $photoDir);
    }

    private function provideInvalidDataForCreateAndUpdate(): iterable
    {
        $data = [
            'not_text' => 'Nice conference',
        ];
        yield 'invalid_data' => [$data, 'Sent data is invalid.'];

        $data = [
            'text' => 'Nice conference',
            'photo' => 'some.png'
        ];
        yield 'invalid_photo' => [$data, 'Photo filename is invalid.'];
    }

    private function getAuthorizationHeaderForUser(ContainerInterface $container, string $userEmail): array
    {
        $user = $container->get(UserRepository::class)->findOneBy(['email' => $userEmail]);
        $token = $container->get(JWTTokenManagerInterface::class)->create($user);
        return ['HTTP_AUTHORIZATION' => 'Bearer ' . $token];
    }

    public function testCreate(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $conferenceRepository = $container->get(ConferenceRepository::class);
        $conference = $conferenceRepository->findOneBy(['slug' => 'berlin-2021']);

        // Send request with valid token and valid data.
        $data = [
            'text' => 'Awesome conference',
            'photo' => 'test_image.gif',
        ];
        $header = $this->getAuthorizationHeaderForUser($container, 'mike@example.com');
        $client->jsonRequest('POST', '/api/conference/' . $conference->getId() . '/comment', $data, $header);
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(json_encode('The comment is created and will be moderated.'), $response->getContent());

        // Check if notifications are sent.
        $this->assertNotificationCount(1);
        $notification1 = $this->getNotifierMessage(0);
        $this->assertNotificationTransportIsEqual($notification1, 'slack');
        $this->assertEmailCount(1);
    }

    /**
     * @dataProvider provideInvalidDataForCreateAndUpdate
     */
    public function testCreateWithInvalidData(array $data, string $errorMessage): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $conferenceRepository = $container->get(ConferenceRepository::class);
        $conference = $conferenceRepository->findOneBy(['slug' => 'berlin-2021']);

        $header = $this->getAuthorizationHeaderForUser($container, 'mike@example.com');
        $client->jsonRequest('POST', '/api/conference/' . $conference->getId() . '/comment', $data, $header);
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(json_encode($errorMessage), $response->getContent());
    }

    public function testCreateUnauthorized(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $conferenceRepository = $container->get(ConferenceRepository::class);
        $conference = $conferenceRepository->findOneBy(['slug' => 'berlin-2021']);

        // Send request with invalid token.
        $header = ['HTTP_AUTHORIZATION' => 'Bearer token'];
        $client->jsonRequest(method: 'POST', uri: '/api/conference/' . $conference->getId() . '/comment', server: $header);
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('Invalid JWT Token', $body['message']);
    }

    public function testGetOne(): void
    {
        $client = static::createClient();

        // Get comment with 'published' status.
        $container = static::getContainer();
        $commentRepository = $container->get(CommentRepository::class);
        $comment = $commentRepository->findOneBy(['email' => 'mike@example.com']);

        $client->jsonRequest('GET', '/api/comment/' . $comment->getId());
        $response = $client->getResponse();

        $commentData = [
            'id' => $comment->getId(),
            'conference_id' => $comment->getConference()->getId(),
            'author' => $comment->getAuthor(),
            'email' => $comment->getEmail(),
            'text' => $comment->getText(),
            'photo' => 'http://localhost/uploads/photos/photo.png'
        ];

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(json_encode($commentData), $response->getContent());
    }

    public function testGetOneUnpublished(): void
    {
        $client = static::createClient();

        // Get comment with 'rejected' status.
        $container = static::getContainer();
        $commentRepository = $container->get(CommentRepository::class);
        $comment = $commentRepository->findOneBy(['email' => 'spam@example.com']);

        $client->jsonRequest('GET', '/api/comment/' . $comment->getId());
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(json_encode('The comment is not published.'), $response->getContent());
    }

    private function provideConferenceComments(): iterable
    {
        yield 'with_comments' => ['berlin-2021', 3];

        yield 'no_comments' => ['paris-2020', 0];
    }

    /**
     * @dataProvider provideConferenceComments
     */
    public function testGetAll(string $conferenceSlug, int $expectedCommentCount): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $conferenceRepository = $container->get(ConferenceRepository::class);
        $conference = $conferenceRepository->findOneBy(['slug' => $conferenceSlug]);

        $client->jsonRequest('GET', '/api/conference/' . $conference->getId() . '/comments');
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount($expectedCommentCount, json_decode($response->getContent(), true));
    }

    public function testUpdate(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $commentRepository = $container->get(CommentRepository::class);
        $comment = $commentRepository->findOneBy(['email' => 'mike@example.com']);

        // Send request with valid token and valid data.
        $data = [
            'text' => 'Nice conference',
            'photo' => 'test_image.gif',
        ];
        $header = $this->getAuthorizationHeaderForUser($container, 'mike@example.com');
        $client->jsonRequest('PUT', '/api/comment/' . $comment->getId(), $data, $header);
        $response = $client->getResponse();
        $updatedComment = $commentRepository->find($comment->getId());

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(json_encode('The comment is updated.'), $response->getContent());
        $this->assertSame('Nice conference', $updatedComment->getText());
        $this->assertSame('test_image.gif', $updatedComment->getPhotoFilename());
    }

    public function testUpdateWithInvalidUser(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $commentRepository = $container->get(CommentRepository::class);
        $comment = $commentRepository->findOneBy(['email' => 'mike@example.com']);

        $header = $this->getAuthorizationHeaderForUser($container, 'user@example.com');
        $client->jsonRequest(method: 'PUT', uri: '/api/comment/' . $comment->getId(), server: $header);
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(json_encode('User user@example.com cannot modify this comment.'), $response->getContent());
    }

    /**
     * @dataProvider provideInvalidDataForCreateAndUpdate
     */
    public function testUpdateWithInvalidData(array $data, string $errorMessage): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $commentRepository = $container->get(CommentRepository::class);
        $comment = $commentRepository->findOneBy(['email' => 'mike@example.com']);

        $header = $this->getAuthorizationHeaderForUser($container, 'mike@example.com');
        $client->jsonRequest('PUT', '/api/comment/' . $comment->getId(), $data, $header);
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(json_encode($errorMessage), $response->getContent());
    }

    public function testDelete(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $commentRepository = $container->get(CommentRepository::class);
        $comment = $commentRepository->findOneBy(['email' => 'mike@example.com']);

        // Send request with valid token.
        $header = $this->getAuthorizationHeaderForUser($container, 'mike@example.com');
        $client->jsonRequest(method: 'DELETE', uri: '/api/comment/' . $comment->getId(), server: $header);
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(json_encode('The comment is deleted.'), $response->getContent());
        $this->assertNull($commentRepository->find($comment->getId()));
    }

    public function testDeleteWithInvalidEmail(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $commentRepository = $container->get(CommentRepository::class);
        $comment = $commentRepository->findOneBy(['email' => 'mike@example.com']);

        // Send request with valid token.
        $header = $this->getAuthorizationHeaderForUser($container, 'user@example.com');
        $client->jsonRequest(method: 'DELETE', uri: '/api/comment/' . $comment->getId(), server: $header);
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(json_encode('User user@example.com cannot delete this comment.'), $response->getContent());
    }

    /**
     * @afterClass
     */
    public static function unlinkImage(): void
    {
        // unlink(dirname(__DIR__, 5) . '/public/uploads/photos/test_image.gif');
    }
}
