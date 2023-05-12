<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\ApiResponseSubscriber;
use App\Exception\ApiHttpException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiResponseSubscriberTest extends KernelTestCase
{
    private EventDispatcher $dispatcher;

    private ApiResponseSubscriber $apiResponseSubscriber;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->apiResponseSubscriber = $container->get(ApiResponseSubscriber::class);

        $this->dispatcher = new EventDispatcher();
    }

    private function provideExceptions(): iterable
    {
        $status = Response::HTTP_BAD_REQUEST;
        yield 'exception_with_status_code' => [new ApiHttpException($status), $status];

        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        yield 'exception_without_status_code' => [new \Exception(), $status];
    }

    /**
     * @dataProvider provideExceptions
     */
    public function testFormatApiException(\Exception $exception, int $status) : void
    {
        $this->dispatcher->addListener('onKernelException', [$this->apiResponseSubscriber, 'onKernelException']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ExceptionEvent($kernel, new Request(attributes: ['_format' => 'json']), HttpKernelInterface::MAIN_REQUEST, $exception);
        $this->dispatcher->dispatch($event, 'onKernelException');

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = $response->getContent();
        $this->assertSame(json_encode($this->createErrorArray($status)), $data);
    }

    public function testFormatApiExceptionWithInvalidFormat() : void
    {
        $this->dispatcher->addListener('onKernelException', [$this->apiResponseSubscriber, 'onKernelException']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ExceptionEvent($kernel, new Request(attributes: ['_format' => 'html']), HttpKernelInterface::MAIN_REQUEST, new \Exception());
        $this->dispatcher->dispatch($event, 'onKernelException');

        $response = $event->getResponse();
        $this->assertNull($response);
    }

    private function createErrorArray(int $status): array
    {
        return [
            'data' => [],
            'error' => [
                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                'title' => Response::$statusTexts[$status],
                'status' => $status
            ],
        ];
    }
}
