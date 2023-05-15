<?php

namespace App\EventSubscriber;

use App\Service\Api\ApiJsonResponseManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(private ApiJsonResponseManager $apiJsonResponseManager)
    {
    }

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
        $event->setResponse($this->apiJsonResponseManager->createApiJsonResponse(error: $exception));
    }
}
