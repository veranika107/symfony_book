<?php

namespace App\Message;

class CommentMessage
{
    public function __construct(
        private int $id,
        private string $reviewUrl,
        private array $context = [],
    ) {
    }

    public function getId(): int
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