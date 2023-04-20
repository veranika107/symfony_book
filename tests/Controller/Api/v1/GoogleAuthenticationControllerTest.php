<?php

namespace App\Tests\Controller\Api\v1;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GoogleAuthenticationControllerTest extends WebTestCase
{
    public function testAuthWithGoogle(): void
    {
        $client = static::createClient();

        $googleUser = new GoogleUser(['email' => 'user@example.com', 'given_name' => 'FirstName', 'family_name' => 'LastName']);

        $container = static::getContainer();
        $googleClient = $this->createMock(GoogleClient::class);
        $googleClient->method('fetchUserFromToken')
            ->willReturn($googleUser);
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->method('getClient')
            ->willReturn($googleClient);
        $container->set(ClientRegistry::class, $clientRegistry);

        $client->jsonRequest('POST', '/api/auth/google', ['token' => 'some_token']);
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $body = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $body);
        $this->assertArrayHasKey('refresh_token', $body);
    }

    public function testAuthWithGoogleWithoutToken(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/auth/google', []);
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(json_encode('Code parameter is missing.'), $response->getContent());
    }

    public function testAuthWithGoogleWithInvalidToken(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $googleClient = $this->createMock(GoogleClient::class);
        $googleClient->method('fetchUserFromToken')
            ->willThrowException(new IdentityProviderException('', 500, ''));
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->method('getClient')
            ->willReturn($googleClient);
        $container->set(ClientRegistry::class, $clientRegistry);

        $client->jsonRequest('POST', '/api/auth/google', ['token' => 'some_token']);
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame(json_encode('Access token is invalid.'), $response->getContent());
    }

    public function testAuthWithGoogleWithInvalidGoogleEmail(): void
    {
        $client = static::createClient();

        $googleUser = new GoogleUser([]);

        $container = static::getContainer();
        $googleClient = $this->createMock(GoogleClient::class);
        $googleClient->method('fetchUserFromToken')
            ->willReturn($googleUser);
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->method('getClient')
            ->willReturn($googleClient);
        $container->set(ClientRegistry::class, $clientRegistry);

        $client->jsonRequest('POST', '/api/auth/google', ['token' => 'some_token']);
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame(json_encode('Google user email should not be empty.'), $response->getContent());
    }
}