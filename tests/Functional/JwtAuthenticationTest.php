<?php

namespace App\Tests\Functional;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\RefreshToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticationTest extends WebTestCase
{
    public function testGetToken(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/auth', ['email' => 'user@example.com', 'password' => 'password']);
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $body = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $body);
        $this->assertArrayHasKey('refresh_token', $body);
    }

    public function testGetRefreshToken(): void
    {
        $client = static::createClient();

        // Create a refresh token for a test user.
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user@example.com']);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $token = RefreshToken::createForUserWithTtl('token', $user, 600);
        $manager->persist($token);
        $manager->flush();

        $client->request('POST', '/api/auth/refresh', ['refresh_token' => $token]);
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $body = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $body);
        $this->assertArrayHasKey('refresh_token', $body);
    }

    public function testFailedGetToken(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/auth', ['email' => 'user@example.com', 'password' => '123']);
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('Invalid credentials.', $body['message']);
    }

    public function testFailedGetRefreshToken(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/refresh', ['refresh_token' => 'token']);
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('JWT Refresh Token Not Found', $body['message']);
    }
}