<?php

namespace App\Controller\Api\v1\Comment;

use App\Attribute\QueryParameter;
use App\DataTransformer\CommentTransformer;
use App\Dto\Comment\CommentInputDto;
use App\Entity\Comment;
use App\Exception\ApiHttpException;
use App\Form\CommentFormType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use App\Security\Voter\CommentVoter;
use App\Service\Api\ApiJsonResponseManager;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\UuidV7;

#[OA\Tag(name: 'Comment')]
class CommentController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $bus,
        private ApiJsonResponseManager $apiJsonResponseManager,
        private CommentTransformer $commentTransformer,
        private SerializerInterface $serializer
    ) {
    }

    #[Route('/api/v1/comment', name: 'api_create_comment', methods: ['POST'], format: 'json')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\RequestBody(
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'conference_id',
                            description: 'Conference ID',
                            type: 'string',
                        ),
                        new OA\Property(
                            property: 'form_data',
                            ref: new Model(type: CommentInputDto::class)
                        )
                    ]
                ),
            )
        ],
    )]
    #[OA\Response(
        response: 200,
        description: 'Successfully created'
    )]
    #[OA\Response(
        response: '400',
        description: 'Bad Request',
        content: new OA\JsonContent(ref: '#/components/schemas/exception')
    )]
    #[OA\Response(
        response: '401',
        description: 'Forbidden',
        content: new OA\JsonContent(ref: '#/components/schemas/exception')
    )]
    #[Security(name: 'Bearer')]
    public function create(
        CommentInputDto $commentInputDto,
        Request $request,
        CommentRepository $commentRepository,
        ConferenceRepository $conferenceRepository,
        #[Autowire('%photo_dir%')] string $photoDir,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!array_key_exists('conference_id', $data))
        {
            throw new ApiHttpException(Response::HTTP_BAD_REQUEST);
        }

        $conferenceId = $data['conference_id'];
        if (!UuidV7::isValid($conferenceId) || !($conference = $conferenceRepository->find($conferenceId))) {
            throw new ApiHttpException(Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(type: CommentFormType::class, options: ['csrf_protection' => false]);
        try {
            $form->submit($this->serializer->normalize($commentInputDto));
        } catch (\Throwable $exception) {
            throw new ApiHttpException(Response::HTTP_BAD_REQUEST);
        }

        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);
            throw new ApiHttpException(statusCode: Response::HTTP_BAD_REQUEST, violations: $errors);
        }

        /** @var Comment $comment */
        $comment = $form->getData();
        $comment->setConference($conference);
        // Check if photo name is sent and if such photo exists on server.
        if ($commentInputDto->photo !== null) {
            if (!file_exists($photoDir . '/' . $commentInputDto->photo)) {
                throw new ApiHttpException(statusCode: Response::HTTP_BAD_REQUEST, violations: ['photo' => ['Photo filename is invalid.']]);
            }
            $comment->setPhotoFilename($commentInputDto->photo);
        }
        $commentRepository->save($comment, true);

        // Dispatch comment message.
        $context = [
            'user_ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('user-agent'),
            'referrer' => $request->headers->get('referer'),
            'permalink' => $request->getUri(),
        ];
        $reviewUrl = $this->generateUrl('review_comment', ['id' => $comment->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->bus->dispatch(new CommentMessage($comment->getId(), $reviewUrl, $context));

        return $this->apiJsonResponseManager->createApiJsonResponse(message: 'The comment is created and will be moderated.');
    }

    #[Route('/api/v1/comment/{comment}', name: 'api_get_comment', methods: ['GET'], format: 'json')]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new OA\JsonContent(ref: '#/components/schemas/comment')
    )]
    #[OA\Response(
        response: '400',
        description: 'Wrong Comment ID',
        content: new OA\JsonContent(ref: '#/components/schemas/exception')
    )]
    public function view(Comment $comment): JsonResponse
    {
        if ($comment->getState() !== 'published') {
            throw new ApiHttpException(Response::HTTP_BAD_REQUEST);
        }

        return $this->apiJsonResponseManager->createApiJsonResponse(data: ($this->commentTransformer)($comment));
    }

    #[Route('/api/v1/comments', name: 'api_get_all_comments', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'conference_id', description: 'Conference ID', in: 'query')]
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
                            items: new OA\Items(ref: '#/components/schemas/comment')
                        )
                    ]
                ),
            )
        ],
    )]
    #[OA\Response(
        response: '400',
        description: 'Wrong Conference ID',
        content: new OA\JsonContent(ref: '#/components/schemas/exception')
    )]
    public function list(
        CommentRepository $commentRepository,
        ConferenceRepository $conferenceRepository,
        #[QueryParameter] string $conferenceId,
    ): JsonResponse
    {
        if (!UuidV7::isValid($conferenceId) || !($conference = $conferenceRepository->find($conferenceId))) {
            throw new ApiHttpException(Response::HTTP_BAD_REQUEST);
        }

        $data = [];
        $comments = $commentRepository->getPublishedCommentsByConference($conference);


        foreach ($comments as $comment) {
            $data[] = ($this->commentTransformer)($comment);
        }

        return $this->apiJsonResponseManager->createApiJsonResponse(data: $data);
    }

    #[Route('/api/v1/comment/{comment}', name: 'api_update_comment', methods: ['PUT'], format: 'json')]
    #[IsGranted(CommentVoter::EDIT, 'comment')]
    #[OA\RequestBody(content: new OA\JsonContent(ref: new Model(type: CommentInputDto::class)))]
    #[OA\Response(
        response: 200,
        description: 'Successfully updated'
    )]
    #[OA\Response(
        response: '400',
        description: 'Bad Request',
        content: new OA\JsonContent(ref: '#/components/schemas/exception')
    )]
    #[OA\Response(
        response: '401',
        description: 'Forbidden',
        content: new OA\JsonContent(ref: '#/components/schemas/exception')
    )]
    #[Security(name: 'Bearer')]
    public function update(
        Comment $comment,
        CommentInputDto $commentInputDto,
        CommentRepository $commentRepository,
        #[Autowire('%photo_dir%')] string $photoDir,
    ): JsonResponse
    {
        $form = $this->createForm(type: CommentFormType::class, data: $comment, options: ['csrf_protection' => false]);

        try {
            $form->submit($this->serializer->normalize($commentInputDto));
        } catch (\Throwable $exception) {
            throw new ApiHttpException(Response::HTTP_BAD_REQUEST);
        }

        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);
            throw new ApiHttpException(statusCode: Response::HTTP_BAD_REQUEST, violations: $errors);
        }

        /** @var Comment $comment */
        $comment = $form->getData();
        if ($commentInputDto->photo !== null) {
            if (!file_exists($photoDir . '/' . $commentInputDto->photo)) {
                throw new ApiHttpException(statusCode: Response::HTTP_BAD_REQUEST, violations: ['photo' => ['Photo filename is invalid.']]);
            }
            $comment->setPhotoFilename($commentInputDto->photo);
        }
        $commentRepository->save($comment, true);

        return $this->apiJsonResponseManager->createApiJsonResponse(message: 'The comment is updated.');
    }

    #[Route('/api/v1/comment/{comment}', name: 'api_delete_comment', methods: ['DELETE'], format: 'json')]
    #[IsGranted(CommentVoter::DELETE, 'comment')]
    #[OA\Response(
        response: 200,
        description: 'Successfully deleted'
    )]
    #[OA\Response(
        response: '400',
        description: 'Wrong Comment ID',
        content: new OA\JsonContent(ref: '#/components/schemas/exception')
    )]
    #[Security(name: 'Bearer')]
    public function delete(
        Comment $comment,
        CommentRepository $commentRepository
    ): JsonResponse
    {
        $commentRepository->remove($comment, true);

        return $this->apiJsonResponseManager->createApiJsonResponse(message: 'The comment is deleted.');
    }

    private function getErrorsFromForm(FormInterface $form): array
    {
        $errors = array();

        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }

        return $errors;
    }
}
