<?php

namespace App\Dto\Comment;

use App\Dto\InputDtoInterface;

class CommentInputDto implements InputDtoInterface
{
    public function __construct(
        public readonly string $text,

        public readonly ?string $photo = null,
    ) {
    }
}
