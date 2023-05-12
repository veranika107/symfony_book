<?php

namespace App\Service\Api;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

class ApiJsonResponseManager
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    private function formatApiData(array $data, string $message, Throwable|FlattenException|NULL $error, array $meta): array
    {
        $formattedData = [
            'data' => $data,
        ];

        if ($message) {
            $formattedData['message'] = $message;
        }

        if ($error) {
            $formattedData['error'] = $error;
        }

        if ($meta) {
            $formattedData['meta'] = $meta;
        }

        return $formattedData;
    }

    public function createApiJsonResponse(array $data = [], string $message = '', Throwable|FlattenException $error = null, array $meta = []): JsonResponse
    {
        $status = Response::HTTP_OK;
        $data = $this->serializer->serialize($this->formatApiData($data, $message, $error, $meta), 'json');

        if ($error) {
            $status = method_exists($error,'getStatusCode') ? $error->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return new JsonResponse(data: $data, status: $status, json: true);
    }

}
