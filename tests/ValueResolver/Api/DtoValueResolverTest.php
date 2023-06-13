<?php

namespace App\Tests\ValueResolver\Api;

use App\Dto\Comment\CommentInputDto;
use App\Exception\ApiHttpException;
use App\ValueResolver\Api\DtoValueResolver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UnwrappingDenormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DtoValueResolverTest extends KernelTestCase
{
    private ValueResolverInterface $dtoValueResolver;

    private Request $request;

    private ArgumentMetadata $argumentMetadata;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $validator = $container->get(ValidatorInterface::class);
        $this->dtoValueResolver = new DtoValueResolver(new Serializer([new UnwrappingDenormalizer(), new ObjectNormalizer()], [new JsonEncoder()]), $validator);

        $this->request = $this->createMock(Request::class);

        $this->argumentMetadata = $this->createMock(ArgumentMetadata::class);
    }

    public function testResolveWithInvalidArgument(): void
    {
        $this->argumentMetadata->method('getType')
            ->willReturn('App\Service\ImageOptimizer');

        $value = $this->dtoValueResolver->resolve($this->request, $this->argumentMetadata);

        $this->assertEmpty($value);
    }

    private function provideBody(): iterable
    {
        $requestBody = [
            'text' => 'Text',
        ];
        yield 'without_nested_array' => [$requestBody];

        $requestBody = [
            'form_data' => [
                'text' => 'Text',
            ]
        ];
        yield 'with_nested_array' => [$requestBody];
    }

    /**
     * @dataProvider provideBody
     */
    public function testResolve(array $requestBody): void
    {
        $this->request->method('getContent')
            ->willReturn(json_encode($requestBody));
        $this->argumentMetadata->method('getType')
            ->willReturn('App\Dto\Comment\CommentInputDto');

        $value = $this->dtoValueResolver->resolve($this->request, $this->argumentMetadata)[0];

        $this->assertInstanceOf(CommentInputDto::class, $value);
        $this->assertEquals(new CommentInputDto('Text'), $value);
    }

    private function provideInvalidBody(): iterable
    {
        $requestBody = [
            'text1' => 'Text',
        ];
        yield 'with_invalid_properties' => [$requestBody];

        $requestBody = [
        ];
        yield 'with_empty_body' => [$requestBody];

        $requestBody = [
            'text' => '',
        ];
        yield 'with_invalid_text' => [$requestBody];

        $requestBody = [
            'text' => 'Text',
            'photo' => '.png'
        ];
        yield 'with_invalid_photo_filename' => [$requestBody];
    }

    /**
     * @dataProvider provideInvalidBody
     */
    public function testResolveWithInvalidBody(array $requestBody): void
    {
        $this->request->method('getContent')
            ->willReturn(json_encode($requestBody));
        $this->argumentMetadata->method('getType')
            ->willReturn('App\Dto\Comment\CommentInputDto');

        $this->expectException(ApiHttpException::class);
        $this->dtoValueResolver->resolve($this->request, $this->argumentMetadata)[0];
    }
}
