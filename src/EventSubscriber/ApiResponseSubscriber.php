<?php

namespace App\EventSubscriber;

use App\Response\ApiJsonResponse;
use App\Serializer\ApiExceptionNormalizer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiResponseSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException'],
        ];
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $requestFormat = $event->getRequest()->getRequestFormat();
        if ($requestFormat !== 'json') {
            return;
        }

        $exception = $event->getThrowable();
        $normalizer = new ApiExceptionNormalizer();
        $errors = $normalizer->normalize($exception);

        $status = method_exists($exception,'getStatusCode') ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        $response = new ApiJsonResponse(errors: $errors, status: $status);
        $event->setResponse($response);
    }
}
