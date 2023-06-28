<?php

namespace App\Tests\Security\Voter;

use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use App\Security\Voter\CommentVoter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CommentVoterTest extends KernelTestCase
{
    public function provideCases()
    {
        yield 'anonymous cannot edit/delete' => [
            null,
            VoterInterface::ACCESS_DENIED
        ];

        yield 'non-owner cannot edit/delete' => [
            'user@example.com',
            VoterInterface::ACCESS_DENIED
        ];

        yield 'owner can edit/delete' => [
            'mike@example.com',
            VoterInterface::ACCESS_GRANTED
        ];
    }

    /**
     * @dataProvider provideCases
     */
    public function testVote(?string $userEmail, $expectedVote) {
        self::bootKernel();
        $container = static::getContainer();
        $comment = $container->get(CommentRepository::class)->findOneBy(['email' => 'mike@example.com']);
        $voter = new CommentVoter();

        if ($userEmail) {
            $user = $container->get(UserRepository::class)->findOneBy(['email' => $userEmail]);
            $token = new UsernamePasswordToken(
                $user, 'credentials'
            );
        } else {
            $token = new NullToken();
        }

        $this->assertSame($expectedVote, $voter->vote($token, $comment, ['EDIT']));
        $this->assertSame($expectedVote, $voter->vote($token, $comment, ['DELETE']));
    }
}
