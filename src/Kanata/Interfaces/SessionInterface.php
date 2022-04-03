<?php

namespace Kanata\Interfaces;

use Psr\Http\Message\ServerRequestInterface as Request;

interface SessionInterface
{
    public static function startSession(Request $request): array;
}
