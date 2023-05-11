<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiHttpException extends HttpException
{
    private array $violations;

    public function __construct(int $statusCode, string $message = '', array $violations = [], \Exception $previous = null, array $headers = array(), $code = 0)
    {
        $this->violations = $violations;

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    public function getViolations(): array
    {
        return $this->violations;
    }
}
