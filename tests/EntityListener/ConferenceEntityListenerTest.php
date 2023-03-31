<?php

namespace App\Tests\EntityListener;

use App\Entity\Conference;
use App\EntityListener\ConferenceEntityListener;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ConferenceEntityListenerTest extends TestCase
{
    private ConferenceEntityListener $conferenceEntityListener;

    protected function setUp(): void
    {
        $this->conferenceEntityListener = new ConferenceEntityListener(new AsciiSlugger());
    }

    public function testPrePersist(): void
    {
        $lifeCycleEvent = $this->createMock(LifecycleEventArgs::class);
        $conference = new Conference('Paris', '2000', false);
        $this->conferenceEntityListener->prePersist($conference, $lifeCycleEvent);

        $this->assertEquals('paris-2000', $conference->getSlug());
    }

    public function testPreUpdate(): void
    {
        $lifeCycleEvent = $this->createMock(LifecycleEventArgs::class);
        $conference = new Conference('Paris', '2000', false, '-');
        $this->conferenceEntityListener->preUpdate($conference, $lifeCycleEvent);

        $this->assertEquals('paris-2000', $conference->getSlug());
    }
}
