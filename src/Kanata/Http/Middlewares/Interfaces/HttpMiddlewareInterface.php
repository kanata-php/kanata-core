<?php

namespace Kanata\Http\Middlewares\Interfaces;

/**
 * This is a pipeline middleware style.
 */
interface HttpMiddlewareInterface
{
    public function __invoke($payload);
}