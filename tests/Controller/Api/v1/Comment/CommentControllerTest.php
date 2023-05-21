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
            'conference_id' => $conference->getId(),
            'form_data' => [
                'text' => 'Awesome conference',
                'photo' => 'test_image.gif',
            ],
        ];
        $header = $this->getAuthorizationHeaderForUser($container, 'mike@example.com');
        $client->jsonRequest('POST', '/api/v1/comment', $data, $header);
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('The comment is created and will be moderated.', $body['message']);

        // Check if notifications are sent.
        $this->assertNotificationCount(1);
        $notification1 = $this->getNotifierMessage(0);
        $this->assertNotificationTransportIsEqual($notification1, 'slack');
        $this->assertEmailCount(1);
    }

    private function provideInvalidDataForCreate(): iterable
    {
        $data = [
            'form_data' => [
                'text' => 'Awesome conference',
                'photo' => 'test_image.gif',
            ],
        ];
        yield 'no_conference_id' => [$data];

        $data = [
            'conference_id' => 'id',
        ];
        yield 'no_form_data' => [$data];

        $data = [
            'conference_id' => 'id',
            'form_data' => [
                'text' => 'Awesome conference',
                'photo' => 'some.png',
            ],
        ];
        yield 'invalid_conference_id' => [$data];

        $container = static::getContainer();
        $conferenceRepository = $container->get(ConferenceRepository::class);
        $conference = $conferenceRepository->findOneBy(['slug' => 'berlin-2021']);

        $data = [
            'conference_id' => $conference->getId(),
            'form_data' => [
                'not_text' => 'Awesome conference',
            ],
        ];
        yield 'invalid_form_data' => [$data];

        $data = [
            'conference_id' => $conference->getId(),
            'form_data' => [
                'text' => 'Nice conference',
                'photo' => 'some.png'
            ]
        ];
        $violations = [
            'photo' => [
                'Photo filename is invalid.'
            ]
        ];
        yield 'invalid_photo' => [$data, $violations];
    }

    /**
     * @dataProvider provideInvalidDataForCreate
     */
    public function testCreateWithInvalidData(array $data, array $violations = []): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $header = $this->getAuthorizationHeaderForUser($container, 'mike@example.com');
        $client->jsonRequest('POST', '/api/v1/comment', $data, $header);
        $response = $client->getResponse();

        $body = json_decode($response->getContent(), true);
        $errorStatus = Response::HTTP_BAD_REQUEST;
        $errorTitle = Response::$statusTexts[$errorStatus];
        $this->assertSame($errorStatus, $response->getStatusCode());
        $this->assertSame($errorTitle, $body['error']['title']);

        if ($violations) {
            $this->assertSame($violations, $body['error']['violations']);
        }
    }

    public function testCreateUnauthorized(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $conferenceRepository = $container->get(ConferenceRepository::class);
        $conference = $conferenceRepository->findOneBy(['slug' => 'berlin-2021']);

        // Send request with invalid token.
        $header = ['HTTP_AUTHORIZATION' => 'Bearer token'];
        $client->jsonRequest(method: 'POST', uri: '/api/v1/comment', server: $header);
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('Invalid JWT Token', $body['message']);
    }

    private function provideDataToTestView(): iterable
    {
        yield 'edited_comment' => ['mike@example.com', 'http://localhost/uploads/photos/photo.png', true];

        yield 'not_edited_comment' => ['fabien@example.com', null, false];
    }

    /**
     * @dataProvider provideDataToTestView
     */
    public function testView(string $email, ?string $photo, bool $edited): void
    {
        $client = static::createClient();

        // Get comment with 'published' status.
        $container = static::getContainer();
        $commentRepository = $container->get(CommentRepository::class);
        $comment = $commentRepository->findOneBy(['email' => $email]);

        $client->jsonRequest('GET', '/api/v1/comment/' . $comment->getId());
        $response = $client->getResponse();

        $commentData = [
            'data' => [
                'id' => $comment->getId(),
                'conference_id' => $comment->getConference()->getId(),
                'author' => $comment->getAuthor(),
                'email' => $comment->getEmail(),
                'text' => $comment->getText(),
                'photo' => $photo,
                'edited' => $edited,
            ]
        ];

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(json_encode($commentData), $response->getContent());
    }

    public function testViewGetOneUnpublished(): void
    {
        $client = static::createClient();

        // Get comment with 'rejected' status.
        $container = static::getContainer();
        $commentRepository = $container->get(CommentRepository::class);
        $comment = $commentRepository->findOneBy(['email' => 'spam@example.com']);

        $client->jsonRequest('GET', '/api/v1/comment/' . $comment->getId());
        $response = $client->getResponse();

        $body = json_decode($response->getContent(), true);
        $errorStatus = Response::HTTP_BAD_REQUEST;
        $errorTitle = Response::$statusTexts[$errorStatus];
        $this->assertSame($errorStatus, $response->getStatusCode());
        $this->assertSame($errorTitle, $body['error']['title']);
    }

    private function provideConferenceComments(): iterable
    {
        yield 'with_comments' => ['berlin-2021', 3];

        yield 'no_comments' => ['paris-2020', 0];
    }

    /**
     * @dataProvider provideConferenceComments
     */
    public function testList(string $conferenceSlug, int $expectedCommentCount): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $conferenceRepository = $container->get(ConferenceRepository::class);
        $conference = $conferenceRepository->findOneBy(['slug' => $conferenceSlug]);

        $client->jsonRequest('GET', '/api/v1/comments?conference_id=' . $conference->getId());
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount($expectedCommentCount, json_decode($response->getContent(), true)['data']);
    }

    private function provideInvalidParametersForList(): iterable
    {
        yield 'without_parameter' => [''];

        yield 'invalid_parameter' => ['?conference_id=id'];
    }

    /**
     * @dataProvider provideInvalidParametersForList
     */
    public function testListWithInvalidParameters(string $parameter): void
    {
        $client = static::createClient();

        $client->jsonRequest('GET', '/api/v1/comments' . $parameter);
        $response = $client->getResponse();

        $body = json_decode($response->getContent(), true);
        $errorStatus = Response::HTTP_BAD_REQUEST;
        $errorTitle = Response::$statusTexts[$errorStatus];
        $this->assertSame($errorStatus, $response->getStatusCode());
        $this->assertSame($errorTitle, $body['error']['title']);
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
        $client->jsonRequest('PUT', '/api/v1/comment/' . $comment->getId(), $data, $header);
        $response = $client->getResponse();
        $updatedComment = $commentRepository->find($comment->getId());

        $this->assertTrue($response->isSuccessful());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('The comment is updated.', $body['message']);
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
        $client->jsonRequest(method: 'PUT', uri: '/api/v1/comment/' . $comment->getId(), server: $header);
        $response = $client->getResponse();

        $body = json_decode($response->getContent(), true);
        $errorStatus = Response::HTTP_FORBIDDEN;
        $errorTitle = Response::$statusTexts[$errorStatus];
        $this->assertSame($errorStatus, $response->getStatusCode());
        $this->assertSame($errorTitle, $body['error']['title']);
    }

    private function provideInvalidDataForUpdate(): iterable
    {
        $data = [
            'not_text' => 'Nice conference',
        ];
        yield 'invalid_data' => [$data];

        $data = [
            'text' => 'Nice conference',
            'photo' => 'some.png'
        ];
        $violations = [
            'photo' => [
                'Photo filename is invalid.'
            ]
        ];
        yield 'invalid_photo' => [$data, $violations];
    }

    /**
     * @dataProvider provideInvalidDataForUpdate
     */
    public function testUpdateWithInvalidData(array $data, array $violations = []): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $commentRepository = $container->get(CommentRepository::class);
        $comment = $commentRepository->findOneBy(['email' => 'mike@example.com']);

        $header = $this->getAuthorizationHeaderForUser($container, 'mike@example.com');
        $client->jsonRequest('PUT', '/api/v1/comment/' . $comment->getId(), $data, $header);
        $response = $client->getResponse();

        $body = json_decode($response->getContent(), true);
        $errorStatus = Response::HTTP_BAD_REQUEST;
        $errorTitle = Response::$statusTexts[$errorStatus];
        $this->assertSame($errorStatus, $response->getStatusCode());
        $this->assertSame($errorTitle, $body['error']['title']);
        if ($violations) {
            $this->assertSame($violations, $body['error']['violations']);
        }
    }

    public function testDelete(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $commentRepository = $container->get(CommentRepository::class);
        $comment = $commentRepository->findOneBy(['email' => 'mike@example.com']);

        // Send request with valid token.
        $header = $this->getAuthorizationHeaderForUser($container, 'mike@example.com');
        $client->jsonRequest(method: 'DELETE', uri: '/api/v1/comment/' . $comment->getId(), server: $header);
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('The comment is deleted.', $body['message']);
        $this->assertNull($commentRepository->find($comment->getId()));
    }

    public function testDeleteWithInvalidUser(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $commentRepository = $container->get(CommentRepository::class);
        $comment = $commentRepository->findOneBy(['email' => 'mike@example.com']);

        // Send request with valid token.
        $header = $this->getAuthorizationHeaderForUser($container, 'user@example.com');
        $client->jsonRequest(method: 'DELETE', uri: '/api/v1/comment/' . $comment->getId(), server: $header);
        $response = $client->getResponse();

        $body = json_decode($response->getContent(), true);
        $errorStatus = Response::HTTP_FORBIDDEN;
        $errorTitle = Response::$statusTexts[$errorStatus];
        $this->assertSame($errorStatus, $response->getStatusCode());
        $this->assertSame($errorTitle, $body['error']['title']);
    }

    /**
     * @afterClass
     */
    public static function unlinkImage(): void
    {
        unlink(dirname(__DIR__, 5) . '/public/uploads/photos/test_image.gif');
    }
}
