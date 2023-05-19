<?php

namespace App\Controller\Web\Conference;

use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use App\Service\ImageUploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ConferenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
    ) {
    }

    #[Route('/')]
    public function indexNoLocale(): Response
    {
        return $this->redirectToRoute('homepage', ['_locale' => 'en']);
    }

    #[Route('/{_locale<%app.supported_locales%>}/', name: 'homepage')]
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ])->setSharedMaxAge(3600);
    }

    #[Route('/{_locale<%app.supported_locales%>}/conference_header', name: 'conference_header')]
    public function conferenceHeader(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/header.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ])->setSharedMaxAge(3600);
    }

    #[Route('/{_locale<%app.supported_locales%>}/conference/{slug}', name: 'conference')]
    public function show(
        Conference $conference,
        Request $request,
        CommentRepository $commentRepository,
        NotifierInterface $notifier,
        ImageUploaderHelper $imageUploaderHelper
    ): Response
    {
        $form = $this->createForm(type: CommentFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->getData();
            $comment->setConference($conference);
            if ($photo = $form['photo']->getData()) {
                try {
                    $filename = $imageUploaderHelper->movePhotoToPermanentDir($photo);
                    $comment->setPhotoFilename($filename);
                } catch (FileException $exception) {
                    $notifier->send(new Notification('Something is wrong with your image. Please try again later.', ['browser']));
                    return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
                }
            }

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];
            $reviewUrl = $this->generateUrl('review_comment', ['id' => $comment->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $this->bus->dispatch(new CommentMessage($comment->getId(), $reviewUrl, $context));

            $notifier->send(new Notification('Thank you for the feedback; your comment will be posted after moderation.', ['browser']));

            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        if ($form->isSubmitted()) {
            $notifier->send(new Notification('Can you check your submission? There are some problems with it.', ['browser']));
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);

        return $this->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comment_form' => $form,
        ]);
    }
}
