<?php

namespace App\Controller\Web\Comment;

use App\Entity\Comment;
use App\Form\CommentFormType;
use App\Repository\CommentRepository;
use App\Security\Voter\CommentVoter;
use App\Service\ImageUploaderHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CommentController extends AbstractController
{
    #[Route('/{_locale<%app.supported_locales%>}/comment/{id}/edit', name: 'app_comment_edit')]
    #[IsGranted(CommentVoter::EDIT, 'comment')]
    public function edit(
        #[MapEntity(expr: 'repository.findPublishedCommentById(id)')]
        Comment $comment,
        Request $request,
        CommentRepository $commentRepository,
        NotifierInterface $notifier,
        ImageUploaderHelper $imageUploaderHelper
    ): Response
    {
        $conference = $comment->getConference();

        $form = $this->createForm(type: CommentFormType::class, data: $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldPhoto = $comment->getPhotoFilename();
            $comment = $form->getData();

            // Update photo filename.
            if ($photo = $form['photo']->getData()) {
                try {
                    $filename = $imageUploaderHelper->movePhotoToPermanentDir($photo);
                    $comment->setPhotoFilename($filename);
                    // Delete old photo.
                    $imageUploaderHelper->deletePhoto($oldPhoto);
                } catch (FileException $exception) {
                    $notifier->send(new Notification('Something is wrong with your image. Please try again later.', ['browser']));
                    return $this->redirectToRoute('app_comment_edit', ['id' => $comment->getId()]);
                }
            }

            $commentRepository->save($comment, true);

            // TODO: Dispatch message to send a comment for verification.

            $notifier->send(new Notification('Thank you for the feedback; your comment is updated.', ['browser']));
            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        if ($form->isSubmitted()) {
            $notifier->send(new Notification('Can you check your submission? There are some problems with it.', ['browser']));
            return $this->redirectToRoute('app_comment_edit', ['id' => $comment->getId()]);
        }

        return $this->render('comment/edit.html.twig', [
            'form' => $form,
            'comment' => $comment,
        ]);
    }
}
