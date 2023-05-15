<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\GoogleAuthenticator;
use App\Service\Api\Google\GoogleUserManager;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticatorTest extends TestCase
{
    private GoogleAuthenticator $googleAuthenticator;

    private ClientRegistry $clientRegistry;

    private GoogleUserManager $googleUserManager;

    private LoggerInterface $logger;

    private Request $request;

    public function setUp(): void
    {
        $this->clientRegistry = $this->createMock(ClientRegistry::class);

        $router = $this->createMock(RouterInterface::class);

        $this->googleUserManager = $this->createMock(GoogleUserManager::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->googleAuthenticator = new GoogleAuthenticator($this->clientRegistry, $router, $this->googleUserManager, $this->logger);

        $this->request = $this->createMock(Request::class);
    }

    public function testSupports(): void
    {
        $request = new Request(attributes: ['_route' => 'connect_google_check']);

        $this->assertTrue($this->googleAuthenticator->supports($request));
    }

    private function setMocksForAuthenticate(): void
    {
        $googleUser = new GoogleUser([]);

        $accessToken = $this->createMock(AccessToken::class);
        $accessToken->method('getToken')
            ->willReturn('token');

        $client = $this->createMock(GoogleClient::class);
        $client->method('getAccessToken')
            ->with([])
            ->willReturn($accessToken);
        $client->method('fetchUserFromToken')
            ->willReturn($googleUser);

        $this->clientRegistry->method('getClient')
            ->willReturn($client);
    }

    public function testAuthenticate(): void
    {
        $this->setMocksForAuthenticate();

        $user = new User(email: 'user@example.com', roles: []);
        $this->googleUserManager->expects($this->once())
            ->method('getUserFromGoogleUser')
            ->willReturn($user);

        $passport = $this->googleAuthenticator->authenticate($this->request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $this->assertSame($user, $passport->getUser());
    }

    public function testAuthenticateInvalidGoogleEmail(): void
    {
        $this->setMocksForAuthenticate();

        $this->googleUserManager->expects($this->once())
            ->method('getUserFromGoogleUser')
            ->willThrowException(new \UnexpectedValueException());

        $this->logger->expects($this->once())
            ->method('error');

        $this->expectException(AuthenticationException::class);
        $this->googleAuthenticator->authenticate($this->request)->getUser();
    }
}
