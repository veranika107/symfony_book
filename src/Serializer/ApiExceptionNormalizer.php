<?php

namespace App\Serializer;

use App\Exception\ApiHttpException;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ApiExceptionNormalizer implements NormalizerInterface
{
    public const FORMAT = 'json';

    private array $defaultContext = [
        'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
    ];

    public function __construct(array $defaultContext = [])
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        $context += $this->defaultContext;

        $status = method_exists($object,'getStatusCode') ? $object->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        $data = [
            'type' => $context['type'],
            'title' => Response::$statusTexts[$status],
            'status' => $status,
        ];

        if ($object instanceof ApiHttpException && $object->getViolations()) {
            $data['violations'] = $object->getViolations();
        }

        return $data;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && ($data instanceof \Exception || $data instanceof FlattenException);
    }
}
