<?php

namespace App\Tests\Serializer;

use App\Exception\ApiHttpException;
use App\Serializer\ApiExceptionNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

class ApiExceptionNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        $normalizer = new ApiExceptionNormalizer();

        $this->assertTrue($normalizer->supportsNormalization(new \Exception(), 'json'));
        $this->assertTrue($normalizer->supportsNormalization(new FlattenException(), 'json'));
        $this->assertFalse($normalizer->supportsNormalization(new \Exception(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), 'json'));
    }

    private function provideExceptions(): iterable
    {
        yield 'exception' => [new \Exception(), Response::HTTP_INTERNAL_SERVER_ERROR];

        yield 'api_exception' => [new ApiHttpException(Response::HTTP_BAD_REQUEST), Response::HTTP_BAD_REQUEST];

        $violations = [
            'photo' => [
                'Invalid photo filename.'
            ],
        ];
        yield 'api_exception_with_violations' => [new ApiHttpException(statusCode: Response::HTTP_BAD_REQUEST, violations: $violations), Response::HTTP_BAD_REQUEST, $violations];
    }

    /**
     * @dataProvider provideExceptions
     */
    public function testNormalize(\Exception $exception, int $status, array $violations = []): void
    {
        $normalizer = new ApiExceptionNormalizer();

        $expected = $this->createErrorArray($status, $violations);
        $this->assertSame($expected, $normalizer->normalize($exception));
    }

    private function createErrorArray(int $status, array $violations = []): array
    {
        $errorArray = [
            'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
            'title' => Response::$statusTexts[$status],
            'status' => $status
        ];

        if ($violations) {
            $errorArray['violations'] = $violations;
        }
        return $errorArray;
    }
}
