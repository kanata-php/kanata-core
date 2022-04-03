<?php

namespace Kanata\Http\Middlewares;

use Kanata\Services\Session;
use Kanata\Exceptions\UnauthorizedException;
use Psr\Http\Message\ServerRequestInterface as Request;

class SessionMiddleware
{
    /**
     * @param Request $request
     * @return Request
     * @throws UnauthorizedException
     */
    public function __invoke(Request $request): Request
    {
        $request->session = Session::startSession($request);
        return $request;
    }
}
