<?php

namespace App\Tests\Response;

use App\Response\ApiJsonResponse;
use PHPUnit\Framework\TestCase;

class ApiJsonResponseTest extends TestCase
{
    private function provideConstructValues(): iterable
    {
        $data = [
            'message' => 'some data in data',
        ];
        $expectedValue = json_encode(['data' => $data]);
        yield 'with_data' => [$expectedValue, $data];

        $error = [
            'title' => 'some error',
        ];
        $expectedValue = json_encode(['data' => [], 'error' => $error]);
        yield 'with_error' => [$expectedValue, [], $error];

        $meta = [
            'pagination' => [
                'limit' => 1,
            ],
        ];
        $expectedValue = json_encode(['data' => [], 'meta' => $meta]);
        yield 'with_meta' => [$expectedValue, [], [], $meta];

        $parameters = [
            'parameter' => 'some',
        ];
        $expectedValue = json_encode(array_merge(['data' => []], $parameters));
        yield 'with_additional_parameters' => [$expectedValue, [], [], [], $parameters];
    }

    /**
     * @dataProvider provideConstructValues
     */
    public function testConstruct(string $expectedValue, array $data = [], array $errors = [], array $meta = [], array $parameters = []) : void
    {
        $response = new ApiJsonResponse($data, $errors, $meta, $parameters);
        $this->assertInstanceOf(ApiJsonResponse::class, $response);
        $this->assertSame($expectedValue, $response->getContent());
    }
}
