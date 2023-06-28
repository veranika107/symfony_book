<?php

namespace App\DataTransformer;

class ConferenceTransformer implements EntityTransformerInterface
{
    public function __invoke(object $conference): array
    {
        return array(
            'id' => $conference->getId(),
            'city' => $conference->getCity(),
            'year' => $conference->getYear(),
            'isInternational' => $conference->isIsInternational(),
        );
    }
}
