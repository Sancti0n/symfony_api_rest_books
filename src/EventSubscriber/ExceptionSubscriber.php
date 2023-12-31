<?php

namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
//use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionSubscriber implements EventSubscriberInterface {

    public function onKernelException(ExceptionEvent $event): void {
        $exception = $event->getThrowable();
        //print($exception instanceof HttpExceptionInterface);
        //dd($exception);
        
        if ($exception instanceof HttpExceptionInterface) {
            $data = [
                'status' => $exception->getStatusCode(),
                'message' => $exception->getMessage()
            ];
            //dd($data);
            $event->setResponse(new JsonResponse($data));
        } else {
            //dd($exception);
            $data = [
                'status' => 500, // Le status n'existe pas car ce n'est pas une exception HTTP, donc on met 500 par défaut.
                'message' => $exception->getMessage()
            ];
            $event->setResponse(new JsonResponse($data));
        }
    }

    public static function getSubscribedEvents(): array {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}


