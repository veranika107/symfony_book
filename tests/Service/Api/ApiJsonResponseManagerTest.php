<?php

namespace App\Tests\Service\Api;

use App\Exception\ApiHttpException;
use App\Service\Api\ApiJsonResponseManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiJsonResponseManagerTest extends KernelTestCase
{
    private function provideConstructValues(): iterable
    {
        $data = [
            'id' => 'some_id',
        ];
        $expectedValue = ['data' => $data];
        yield 'with_data' => [$expectedValue, $data];

        $message = 'Some message';
        $expectedValue = ['data' => [], 'message' => $message];
        yield 'with_message' => [$expectedValue, [], $message];

        $error = new ApiHttpException(Response::HTTP_INTERNAL_SERVER_ERROR);
        $expectedValue = [
            'data' => [],
            'error' => [
                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                'title' => Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR],
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR
            ]
        ];
        yield 'with_error' => [$expectedValue, [], '', $error];

        $meta = [
            'pagination' => [
                'limit' => 1,
            ],
        ];
        $expectedValue = ['data' => [], 'meta' => $meta];
        yield 'with_meta' => [$expectedValue, [], '', null, $meta];
    }

    /**
     * @dataProvider provideConstructValues
     */
    public function testConstruct(array $expectedValue, array $data = [], string $message = '', mixed $error = null, array $meta = []) : void
    {
        self::bootKernel();
        $container = static::getContainer();
        $apiResponseManager = $container->get(ApiJsonResponseManager::class);
        $response = $apiResponseManager->createApiJsonResponse($data, $message, $error, $meta);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(json_encode($expectedValue), $response->getContent());
    }
}
