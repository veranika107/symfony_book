<?php

namespace App\Tests\Controller\Api\v1\Conference;

use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConferenceControllerTest extends WebTestCase
{

    public function testView(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $conferenceRepository = $container->get(ConferenceRepository::class);
        $conference = $conferenceRepository->findOneBy(['slug' => 'amsterdam-2019']);

        $client->jsonRequest('GET', '/api/v1/conference/' . $conference->getId());
        $response = $client->getResponse();

        $conferenceData = [
            'data' => [
                'id' => $conference->getId(),
                'city' => $conference->getCity(),
                'year' => $conference->getYear(),
                'isInternational' => $conference->isIsInternational(),
            ]
        ];

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(json_encode($conferenceData), $response->getContent());
    }

    public function testList(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $conferenceRepository = $container->get(ConferenceRepository::class);
        $conferences = $conferenceRepository->findAll();

        $client->jsonRequest('GET', '/api/v1/conferences');
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(count($conferences), json_decode($response->getContent(), true)['data']);
    }
}
