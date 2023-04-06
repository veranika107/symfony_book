<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class UserTest extends TestCase
{
    public function testConstruct(): void
    {
        $user = new User('user@example.com', ['ROLE_USER']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertInstanceOf(UuidV7::class, $user->getId());
        $this->assertSame('user@example.com', $user->getEmail());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
        $this->assertNull($user->getUserFirstName());
        $this->assertNull($user->getUserLastName());
        $this->assertEmpty($user->getPassword());
    }

    public function testProperties(): void
    {
        $user = new User('', []);
        $user->setEmail('user@example.com');
        $this->assertSame('user@example.com', $user->getEmail());

        $user->setRoles(['ROLE_USER']);
        $this->assertSame(['ROLE_USER'], $user->getRoles());

        $user->setUserFirstName('FirstName');
        $this->assertSame('FirstName', $user->getUserFirstName());

        $user->setUserLastName('LastName');
        $this->assertSame('LastName', $user->getUserLastName());

        $user->setPassword('password');
        $this->assertSame('password', $user->getPassword());
    }

    public function testToString(): void
    {
        $user = new User('user@example.com', ['ROLE_USER']);

        $this->assertEquals('user@example.com', $user);
    }
}
