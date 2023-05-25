<?php

namespace App\DataTransformer;

use App\Entity\Comment;
use Symfony\Component\HttpFoundation\RequestStack;

class CommentTransformer
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function __invoke(Comment $comment): array
    {
        $request = $this->requestStack->getCurrentRequest();

        return array(
            'id' => $comment->getId(),
            'conference_id' => $comment->getConference()->getId(),
            'author' => $comment->getAuthor(),
            'email' => $comment->getEmail(),
            'text' => $comment->getText(),
            'photo' => $comment->getPhotoFilename() ? $request->getUriForPath('/uploads/photos/' . $comment->getPhotoFilename()) : null,
            'edited' => (bool)$comment->getUpdatedAt(),
        );
    }
}
