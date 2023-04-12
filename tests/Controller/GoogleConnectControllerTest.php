<?php

namespace App\Tests\Controller;

use App\Entity\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use KnpU\OAuth2ClientBundle\Exception\MissingAuthorizationCodeException;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GoogleConnectControllerTest extends WebTestCase
{
    public function testGoogleAuthentication(): void
    {
        $client = static::createClient();

        $googleUser = new GoogleUser(['email' => 'user@example.com', 'given_name' => 'FirstName', 'family_name' => 'LastName']);

        $accessToken = $this->createMock(AccessToken::class);
        $accessToken->method('getToken')
            ->willReturn('token');

        $googleClient = $this->createMock(GoogleClient::class);
        $googleClient->method('getAccessToken')
            ->willReturn($accessToken);
        $googleClient->method('fetchUserFromToken')
            ->willReturn($googleUser);

        $container = static::getContainer();
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->method('getClient')
            ->willReturn($googleClient);
        $container->set(ClientRegistry::class, $clientRegistry);

        $client->request('GET', '/connect/google/check');

        // Current authenticated user.
        $user = $container->get('security.untracked_token_storage')->getToken()->getUser();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('user@example.com', $user->getEmail());
    }

    public function testFailedGoogleAuthentication(): void
    {
        $client = static::createClient();

        $googleClient = $this->createMock(GoogleClient::class);
        $googleClient->method('getAccessToken')
            ->willThrowException(new MissingAuthorizationCodeException());

        $container = static::getContainer();
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->method('getClient')
            ->willReturn($googleClient);
        $container->set(ClientRegistry::class, $clientRegistry);

        $client->request('GET', '/connect/google/check');
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame('Authentication failed! Did you authorize our app?', $response->getContent());
    }
}