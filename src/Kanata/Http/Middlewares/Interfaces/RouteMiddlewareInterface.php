<?php

namespace Kanata\Http\Middlewares\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface RouteMiddlewareInterface
{
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}