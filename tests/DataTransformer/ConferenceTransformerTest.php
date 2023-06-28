<?php

namespace App\Tests\DataTransformer;

use App\DataTransformer\ConferenceTransformer;
use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ConferenceTransformerTest extends KernelTestCase
{
    public function testTransformer(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $conference = $container->get(ConferenceRepository::class)->findOneBy(['slug' => 'amsterdam-2019']);

        $expectedData = [
            'id' => $conference->getId(),
            'city' => $conference->getCity(),
            'year' => $conference->getYear(),
            'isInternational' => $conference->isIsInternational(),
        ];

        $conferenceTransformer = new ConferenceTransformer();
        $data = $conferenceTransformer($conference);

        $this->assertSame($expectedData, $data);
    }
}
