<?php

namespace App\Tests\ValueResolver;

use App\Attribute\QueryParameter;
use App\ValueResolver\QueryParameterValueResolver;
use Doctrine\ORM\Mapping\PostRemove;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class QueryParameterValueResolverTest extends TestCase
{
    private ValueResolverInterface $resolver;

    protected function setUp(): void
    {
        $this->resolver = new QueryParameterValueResolver();
    }

    private function provideTestResolve(): iterable
    {
        yield 'string_argument' => ['string', 'string'];

        yield 'int_argument' => [123, 'int'];

        yield 'float_argument' => [12.345, 'float'];

        yield 'bool_argument' => [false, 'bool'];

        yield 'camel_case_argument' => [false, 'bool', 'par_am_et_er', 'ParAmEtEr'];
    }

    /**
     * @dataProvider provideTestResolve
     */
    public function testResolve(mixed $queryParameter, string $argumentType, string $queryParameterName = 'value', string $argumentName = 'value'): void
    {
        $request = new Request([$queryParameterName => $queryParameter]);
        $argument = new ArgumentMetadata($argumentName, $argumentType, false, false, false, attributes: [new QueryParameter()]);
        $value = $this->resolver->resolve($request, $argument);

        $this->assertEquals([$queryParameter], $value);
    }

    private function provideUnresolvableArguments(): iterable
    {
        $argument = new ArgumentMetadata('name', 'string', false, false, false, attributes: [new PostRemove()]);
        yield 'invalid_argument_attribute' => [$argument];

        $argument = new ArgumentMetadata('name', 'string', false, false, false, attributes: []);
        yield 'no_argument_attribute' => [$argument];

        $argument = new ArgumentMetadata('value', 'string', false, false, false, isNullable: true, attributes: [new QueryParameter()]);
        yield 'nullable_argument_with_no_query_parameter' => [$argument];

        $argument = new ArgumentMetadata('value', 'string', false, true, 'string', attributes: [new QueryParameter()]);
        yield 'default_value_with_no_query_parameter' => [$argument];

        $argument = new ArgumentMetadata('value', 'string', false, false, false, attributes: [new QueryParameter()]);
        yield 'missing_query_parameter' => [$argument, BadRequestException::class, 'Missing query parameter "value".'];

        $request = new Request(['value' => 'string']);
        $argument = new ArgumentMetadata('value', 'type', false, false, false, attributes: [new QueryParameter()]);
        yield 'invalid_argument_type' => [$argument, \LogicException::class, '#[QueryParameter] cannot be used on controller argument "$value" of type "type".', $request];

        $request = new Request(['value' => 'string']);
        $argument = new ArgumentMetadata('value', 'int', false, false, false, attributes: [new QueryParameter()]);
        yield 'invalid_query_parameter' => [$argument, BadRequestException::class, 'Invalid query parameter "value".', $request];
    }

    /**
     * @dataProvider provideUnresolvableArguments
     */
    public function testResolveWithUnresolvableArguments(ArgumentMetadata $argument, string $exceptionClass = null, string $exceptionMessage = null, Request $request = null): void
    {
        $request = $request ?? new Request();

        if ($exceptionMessage) {
            $this->expectException($exceptionClass);
            $this->expectExceptionMessage($exceptionMessage);
        }

        $value = $this->resolver->resolve($request, $argument);

        $this->assertEmpty($value);
    }
}
