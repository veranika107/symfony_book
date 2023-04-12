<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\GoogleAuthenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticatorTest extends TestCase
{
    private GoogleAuthenticator $googleAuthenticator;

    private ClientRegistry $clientRegistry;

    private UserRepository $userRepository;

    private Request $request;

    public function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->clientRegistry = $this->createMock(ClientRegistry::class);

        $router = $this->createMock(RouterInterface::class);

        $this->googleAuthenticator = new GoogleAuthenticator($this->clientRegistry, $router, $this->userRepository);

        $this->request = $this->createMock(Request::class);
    }

    public function testSupports(): void
    {
        $request = new Request(attributes: ['_route' => 'connect_google_check']);

        $this->assertTrue($this->googleAuthenticator->supports($request));
    }

    private function setMocksForAuthenticate(): void
    {
        $googleUser = new GoogleUser(['email' => 'user@example.com', 'given_name' => 'FirstName', 'family_name' => 'LastName']);

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

    public function testAuthenticateExistingUser(): void
    {
        $this->setMocksForAuthenticate();

        $user = new User(email: 'user@example.com', roles: []);
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);
        $this->userRepository->expects($this->never())
            ->method('save');

        $passport = $this->googleAuthenticator->authenticate($this->request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $this->assertSame($user, $passport->getUser());
    }

    public function testAuthenticateNewUser(): void
    {
        $this->setMocksForAuthenticate();

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $this->userRepository->expects($this->once())
            ->method('save');

        $passport = $this->googleAuthenticator->authenticate($this->request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);

        $user = $passport->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('user@example.com', $user->getEmail());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
        $this->assertSame('FirstName', $user->getUserFirstName());
        $this->assertSame('LastName', $user->getUserLastName());
    }
}
