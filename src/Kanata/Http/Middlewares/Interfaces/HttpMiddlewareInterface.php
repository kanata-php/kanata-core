<?php

namespace Kanata\Http\Middlewares\Interfaces;

interface HttpMiddlewareInterface
{
    public function __invoke($payload);
}