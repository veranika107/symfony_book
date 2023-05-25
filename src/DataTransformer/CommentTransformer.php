<?php

namespace App\DataTransformer;

use Symfony\Component\HttpFoundation\RequestStack;

class CommentTransformer implements EntityTransformerInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function __invoke(object $comment): array
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
