<?php

namespace Kanata\Exceptions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;
use Throwable;

class ErrorHandler extends SlimErrorHandler
{
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        if (is_a($exception, HttpNotFoundException::class)) {
            $path = $exception->getRequest()->getUri()->getPath();
            $exception = new HttpNotFoundException($exception->getRequest(), 'Not Found! (' . $path . ')', $exception->getPrevious());
        }

        return parent::__invoke($request, $exception, $displayErrorDetails, $logErrors, $logErrorDetails);
    }

    protected function logError(string $error): void
    {
        logger()->error($error);
    }
}