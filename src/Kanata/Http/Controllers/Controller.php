<?php

namespace Kanata\Http\Controllers;

use Kanata\Services\Container;

abstract class Controller
{
    public function __construct(
        protected Container $container
    ) {}
}
