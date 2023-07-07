<?php

namespace App\Controller\Api\v1\Conference;

use App\DataTransformer\ConferenceTransformer;
use App\Entity\Conference;
use App\Repository\ConferenceRepository;
use App\Service\Api\ApiJsonResponseManager;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag(name: 'Conference')]
class ConferenceController extends AbstractController
{
    public function __construct(
        private ApiJsonResponseManager $apiJsonResponseManager,
        private ConferenceTransformer $conferenceTransformer,
    ) {
    }

    #[Route('/api/v1/conference/{conference}', name: 'api_get_conference', methods: ['GET'], format: 'json')]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new OA\JsonContent(ref: '#/components/schemas/conference')
    )]
    public function view(Conference $conference): JsonResponse
    {
        return $this->apiJsonResponseManager->createApiJsonResponse(data: ($this->conferenceTransformer)($conference));
    }

    #[Route('/api/v1/conferences', name: 'api_get_all_conferences', methods: ['GET'], format: 'json')]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/conference')
                        )
                    ]
                ),
            )
        ],
    )]
    public function list(ConferenceRepository $conferenceRepository): JsonResponse
    {
        $data = [];
        $conferences = $conferenceRepository->findAll();

        foreach ($conferences as $conference) {
            $data[] = ($this->conferenceTransformer)($conference);
        }

        return $this->apiJsonResponseManager->createApiJsonResponse(data: $data);
    }
}
