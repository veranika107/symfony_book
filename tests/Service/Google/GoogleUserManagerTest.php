<?php

namespace App\Tests\Service\Google;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Google\GoogleUserManager;
use League\OAuth2\Client\Provider\GoogleUser;
use PHPUnit\Framework\TestCase;

class GoogleUserManagerTest extends TestCase
{
    private GoogleUserManager $googleUserManager;

    private UserRepository $userRepository;

    public function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->googleUserManager = new GoogleUserManager($this->userRepository);
    }

    public function testCreateUserFromGoogleUserWithExistingUser(): void {
        $googleUser = new GoogleUser(['email' => 'user@example.com', 'given_name' => 'FirstName', 'family_name' => 'LastName']);
        $user = new User(email: 'user@example.com', roles: []);

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);
        $this->userRepository->expects($this->never())
            ->method('save');

        $existingUser = $this->googleUserManager->getUserFromGoogleUser($googleUser);
        $this->assertInstanceOf(User::class, $existingUser);
        $this->assertSame($user, $existingUser);
    }

    public function testCreateUserFromGoogleUserWithNewUser(): void {
        $googleUser = new GoogleUser(['email' => 'user@example.com', 'given_name' => 'FirstName', 'family_name' => 'LastName']);

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $this->userRepository->expects($this->once())
            ->method('save');

        $user = $this->googleUserManager->getUserFromGoogleUser($googleUser);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('user@example.com', $user->getEmail());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
        $this->assertSame('FirstName', $user->getUserFirstName());
        $this->assertSame('LastName', $user->getUserLastName());
    }

    public function testCreateUserFromGoogleUserWithInvalidGoogleEmail(): void {
        $googleUser = new GoogleUser(['email' => null]);

        $this->expectException(\UnexpectedValueException::class);
        $this->googleUserManager->getUserFromGoogleUser($googleUser);
    }
}
