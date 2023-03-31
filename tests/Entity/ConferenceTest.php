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
        $this->assertEquals(null, $conference->getSlug());
        $this->assertInstanceOf(ArrayCollection::class, $conference->getComments());
    }

    public function testProperties(): void
    {
        $conference = new Conference('', '', true);
        $conference->setCity('London');
        $this->assertEquals('London', $conference->getCity());

        $conference->setYear('2020');
        $this->assertEquals('2020', $conference->getYear());

        $conference->setIsInternational(false);
        $this->assertEquals(false, $conference->isIsInternational());

        $comment1 = new Comment('', '' , '');
        $comment2 = new Comment('', '' , '');
        $conference->addComment($comment1);
        $conference->addComment($comment2);
        $this->assertEquals(2, count($conference->getComments()->getValues()));

        $conference->removeComment($comment1);
        $this->assertEquals(1, count($conference->getComments()->getValues()));

        $conference->setSlug('city');
        $this->assertEquals('city', $conference->getSlug());
    }
}
