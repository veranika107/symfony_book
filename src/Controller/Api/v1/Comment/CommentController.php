<?php

namespace App\Controller\Api\v1\Comment;

use App\DataTransformer\CommentTransformer;
use App\Entity\Comment;
use App\Entity\User;
use App\Exception\ApiHttpException;
use App\Form\CommentFormType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use App\Security\Voter\CommentVoter;
use App\Service\Api\ApiJsonResponseManager;
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
use Symfony\Component\Uid\UuidV7;

class CommentController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $bus,
        private ApiJsonResponseManager $apiJsonResponseManager,
        private CommentTransformer $commentTransformer,
    ) {
    }

    #[Route('/api/v1/comment', name: 'api_create_comment', methods: ['POST'], format: 'json')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function create(
        Request $request,
        CommentRepository $commentRepository,
        ConferenceRepository $conferenceRepository,
        #[Autowire('%photo_dir%')] string $photoDir,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!array_key_exists('conference_id', $data) || !array_key_exists('form_data', $data))
        {
            throw new ApiHttpException(Response::HTTP_BAD_REQUEST);
        }

        $conferenceId = $data['conference_id'];
        if (!UuidV7::isValid($conferenceId) || !($conference = $conferenceRepository->find($conferenceId))) {
            throw new ApiHttpException(Response::HTTP_BAD_REQUEST);
        }

        $formData = $data['form_data'];
        $form = $this->createForm(type: CommentFormType::class, options: ['csrf_protection' => false]);
        try {
            $form->submit($formData);
        } catch (\TypeError $exception) {
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
        if (array_key_exists('photo', $formData)) {
            if (!file_exists($photoDir . '/' . $formData['photo'])) {
                throw new ApiHttpException(statusCode: Response::HTTP_BAD_REQUEST, violations: ['photo' => ['Photo filename is invalid.']]);
            }
            $comment->setPhotoFilename($formData['photo']);
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

        return$this->apiJsonResponseManager->createApiJsonResponse(message: 'The comment is created and will be moderated.');
    }

    #[Route('/api/v1/comment/{comment}', name: 'api_get_comment', methods: ['GET'], format: 'json')]
    public function view(Comment $comment): JsonResponse
    {
        if ($comment->getState() !== 'published') {
            throw new ApiHttpException(Response::HTTP_BAD_REQUEST);
        }

        return $this->apiJsonResponseManager->createApiJsonResponse(data: ($this->commentTransformer)($comment));
    }

    #[Route('/api/v1/comments', name: 'api_get_all_comments', methods: ['GET'], format: 'json')]
    public function list(
        Request $request,
        CommentRepository $commentRepository,
        ConferenceRepository $conferenceRepository,
    ): JsonResponse
    {
        $conferenceId = $request->query->get('conference_id');
        if (!$conferenceId) {
            throw new ApiHttpException(Response::HTTP_BAD_REQUEST);
        }

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
    public function update(
        Comment $comment,
        Request $request,
        CommentRepository $commentRepository,
        #[Autowire('%photo_dir%')] string $photoDir,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $form = $this->createForm(type: CommentFormType::class, data: $comment, options: ['csrf_protection' => false]);

        try {
            $form->submit($data);
        } catch (\Throwable $exception) {
            throw new ApiHttpException(Response::HTTP_BAD_REQUEST);
        }

        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);
            throw new ApiHttpException(statusCode: Response::HTTP_BAD_REQUEST, violations: $errors);
        }

        /** @var Comment $comment */
        $comment = $form->getData();
        if (array_key_exists('photo', $data)) {
            if (!file_exists($photoDir . '/' . $data['photo'])) {
                throw new ApiHttpException(statusCode: Response::HTTP_BAD_REQUEST, violations: ['photo' => ['Photo filename is invalid.']]);
            }
            $comment->setPhotoFilename($data['photo']);
        }
        $commentRepository->save($comment, true);

        return $this->apiJsonResponseManager->createApiJsonResponse(message: 'The comment is updated.');
    }

    #[Route('/api/v1/comment/{comment}', name: 'api_delete_comment', methods: ['DELETE'], format: 'json')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(
        Comment $comment,
        CommentRepository $commentRepository
    ): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($comment->getEmail() !== $currentUser->getEmail()) {
            throw new ApiHttpException(Response::HTTP_FORBIDDEN);
        }
        $commentRepository->remove($comment, true);

        return $this->apiJsonResponseManager->createApiJsonResponse(message: 'The comment is deleted.');
    }

    private function getErrorsFromForm(FormInterface $form)
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
