<?php

namespace App\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiJsonResponse extends JsonResponse
{
    public function __construct(array $data = [], array $errors = [], array $meta = [], array $parameters = [], int $status = 200, array $headers = [], bool $json = false)
    {
        $formattedData = $this->formatApiData($data, $errors, $meta, $parameters);

        parent::__construct($formattedData, $status, $headers, $json);
    }

    private function formatApiData(array $data, array $errors, array $meta, array $parameters): array
    {
        $formattedData = [
            'data' => $data,
        ];

        if ($errors) {
            $formattedData['error'] = $errors;
        }

        if ($meta) {
            $formattedData['meta'] = $meta;
        }

        if ($parameters) {
            $formattedData = array_merge($formattedData, $parameters);
        }

        return $formattedData;
    }
}
