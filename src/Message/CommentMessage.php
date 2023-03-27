<?php

namespace App\Message;

use Symfony\Component\Uid\UuidV7;

class CommentMessage
{
    public function __construct(
        private UuidV7 $id,
        private string $reviewUrl,
        private array $context = [],
    ) {
    }

    public function getId(): UuidV7
    {
        return $this->id;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getReviewUrl(): string
    {
        return $this->reviewUrl;
    }
}