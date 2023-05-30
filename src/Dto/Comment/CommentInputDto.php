<?php

namespace App\Dto\Comment;

class CommentInputDto
{
    public function __construct(
        public readonly string $text,

        public readonly ?string $photoFilename,
    ) {
    }
}
