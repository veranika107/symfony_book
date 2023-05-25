<?php

namespace App\DataTransformer;

/**
 * Transforms entity to array.
 */
interface EntityTransformerInterface
{
    public function __invoke(object $object): array;
}
