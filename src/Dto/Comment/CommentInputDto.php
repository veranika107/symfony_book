<?php

namespace App\Dto\Comment;

use App\Dto\InputDtoInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CommentInputDto implements InputDtoInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Text is required.')]
        #[Assert\Length(
            min: 4,
            max: 500,
            minMessage: 'Text field must be at least {{ limit }} characters long.',
            maxMessage: 'Text field cannot be longer than {{ limit }} characters.',
        )]
        public readonly string $text,

        #[Assert\Length(
            min: 5,
            max: 255,
            minMessage: 'Photo filename must be at least {{ limit }} characters long.',
            maxMessage: 'Photo filename cannot be longer than {{ limit }} characters.',
        )]
        #[Assert\Regex(
            pattern: '/.+\.(gif|jpe?g|png)$/i',
            message: 'The given string does not start with image file name(required at least one character). Therefore, it is not a valid image file extension. (jpg, jpeg, png, gif).',
        )]
        public readonly ?string $photo = null,
    ) {
    }
}
