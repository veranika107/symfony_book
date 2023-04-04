<?php

namespace App\Tests\Entity;

use App\Entity\Comment;
use App\Entity\Conference;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class ConferenceTest extends TestCase
{
    public function testConstruct(): void
    {
        $conference = new Conference('City', '2000', true);
        $this->assertInstanceOf(Conference::class, $conference);
        $this->assertInstanceOf(UuidV7::class, $conference->getId());
        $this->assertNull($conference->getSlug());
        $this->assertInstanceOf(ArrayCollection::class, $conference->getComments());
    }

    public function testProperties(): void
    {
        $conference = new Conference('', '', true);
        $conference->setCity('London');
        $this->assertSame('London', $conference->getCity());

        $conference->setYear('2020');
        $this->assertSame('2020', $conference->getYear());

        $conference->setIsInternational(false);
        $this->assertFalse($conference->isIsInternational());

        $comment1 = new Comment('', '' , '');
        $comment2 = new Comment('', '' , '');
        $conference->addComment($comment1);
        $conference->addComment($comment2);
        $this->assertSame(2, count($conference->getComments()->getValues()));

        $conference->removeComment($comment1);
        $this->assertSame(1, count($conference->getComments()->getValues()));

        $conference->setSlug('city');
        $this->assertSame('city', $conference->getSlug());
    }
}
