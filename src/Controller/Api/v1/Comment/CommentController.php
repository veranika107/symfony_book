<?php

namespace App\Controller\Api\v1\Comment;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Entity\User;
use App\Form\CommentFormType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
            return $this->json('Sent data is invalid.', Response::HTTP_BAD_REQUEST);
        }

        $conferenceId = $data['conference_id'];
        if (!UuidV7::isValid($conferenceId) || !($conference = $conferenceRepository->find($conferenceId))) {
            return $this->json('conference_id is invalid.', Response::HTTP_BAD_REQUEST);
        }

        $formData = $data['form_data'];
        $form = $this->createForm(type: CommentFormType::class);
        try {
            $form->submit($formData);
        } catch (\Throwable $exception) {
            return $this->json('Sent data is invalid.', Response::HTTP_BAD_REQUEST);
        }

        /** @var Comment $comment */
        $comment = $form->getData();
        $comment->setConference($conference);
        // Check if photo name is sent and if such photo exists on server.
        if (array_key_exists('photo', $formData)) {
            if (!file_exists($photoDir . '/' . $formData['photo'])) {
                return $this->json('Photo filename is invalid.', Response::HTTP_BAD_REQUEST);
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

        return $this->json('The comment is created and will be moderated.', Response::HTTP_CREATED);
    }

    #[Route('/api/v1/comment/{comment}', name: 'api_get_comment', methods: ['GET'], format: 'json')]
    public function view(Comment $comment, Request $request): JsonResponse
    {
        if ($comment->getState() !== 'published') {
            return $this->json('The comment is not published.', Response::HTTP_BAD_REQUEST);
        }

        $data = $this->serializeComment($comment, $request);

        return $this->json($data);
    }

    #[Route('/api/v1/comments', name: 'api_get_all_comments', methods: ['GET'], format: 'json')]
    public function list(
        Request $request,
        CommentRepository $commentRepository,
        ConferenceRepository $conferenceRepository,
    ): JsonResponse
    {
        $conferenceId = $request->query->get('conference_id');
        if (!$conferenceId)
        {
            return $this->json('conference_id parameter is missing.', Response::HTTP_BAD_REQUEST);
        }

        if (!UuidV7::isValid($conferenceId) || !($conference = $conferenceRepository->find($conferenceId))) {
            return $this->json('conference_id parameter is invalid.', Response::HTTP_BAD_REQUEST);
        }

        $data = [];
        $comments = $commentRepository->getPublishedCommentsByConference($conference);

        foreach ($comments as $comment) {
            $data[] = $this->serializeComment($comment, $request);
        }

        return $this->json($data);
    }

    #[Route('/api/v1/comment/{comment}', name: 'api_update_comment', methods: ['PUT'], format: 'json')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function update(
        Comment $comment,
        Request $request,
        CommentRepository $commentRepository,
        #[Autowire('%photo_dir%')] string $photoDir,
    ): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($comment->getEmail() !== $currentUser->getEmail()) {
            return $this->json(sprintf('User %s cannot modify this comment.', $currentUser->getEmail()), Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $form = $this->createForm(type: CommentFormType::class, data: $comment);

        try {
            $form->submit($data);
        } catch (\Throwable $exception) {
            return $this->json('Sent data is invalid.', Response::HTTP_BAD_REQUEST);
        }

        /** @var Comment $comment */
        $comment = $form->getData();
        if (array_key_exists('photo', $data)) {
            if (!file_exists($photoDir . '/' . $data['photo'])) {
                return $this->json('Photo filename is invalid.', Response::HTTP_BAD_REQUEST);
            }
            $comment->setPhotoFilename($data['photo']);
        }
        $commentRepository->save($comment, true);

        return $this->json('The comment is updated.');
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
            return $this->json(sprintf('User %s cannot delete this comment.', $currentUser->getEmail()), Response::HTTP_BAD_REQUEST);
        }
        $commentRepository->remove($comment, true);

        return $this->json('The comment is deleted.');
    }

    private function serializeComment(Comment $comment, Request $request)
    {
        return array(
            'id' => $comment->getId(),
            'conference_id' => $comment->getConference()->getId(),
            'author' => $comment->getAuthor(),
            'email' => $comment->getEmail(),
            'text' => $comment->getText(),
            'photo' => $comment->getPhotoFilename() ? $request->getUriForPath('/uploads/photos/' . $comment->getPhotoFilename()) : null,
        );
    }
}
