<?php

namespace Kanata\Commands\Traits;

use Kanata\Http\Middlewares\FormMiddleware;
use Kanata\Http\Middlewares\SessionMiddleware;
use Kanata\Services\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use voku\helper\Hooks;

trait HttpRequest
{
    private function addMiddlewareHttpRequest(ServerRequestInterface $psr7Request): ServerRequestInterface
    {
        /**
         * Action: http_middleware
         * Description: Allows HTTP middleware execution on Psr7 Request.
         * @param ServerRequestInterface $request
         */
        $psr7Request = Hooks::getInstance()->apply_filters('http_middleware', $psr7Request);

        /**
         * Action: http_session_middleware
         * Description: Enable/Disable Session Middleware to HTTP request.
         * @param bool $active
         */
        if (Hooks::getInstance()->apply_filters('http_session_middleware', true)) {
            $psr7Request = (new SessionMiddleware)($psr7Request);
        }

        /**
         * Action: http_form_middleware
         * Description: Enable/Disable Form Middleware to HTTP request, useful for cached input.
         * @param bool $active
         */
        if (Hooks::getInstance()->apply_filters('http_form_middleware', true)) {
            $psr7Request = (new FormMiddleware)($psr7Request);
        }

        return $psr7Request;
    }

    private function getUnauthorizedView(): string
    {
        /**
         * Action: unauthorized_view
         * Description: Customize unauthorized view.
         * @param string
         */
        $unauthorized_view = Hooks::getInstance()->apply_filters('unauthorized_view', 'core::exceptions/unauthorized');
        return container()->view->render($unauthorized_view);
    }

    private function processResponse(ServerRequestInterface $psr7Request, ResponseInterface $psr7Response)
    {
        Session::addCookiesToResponse($psr7Request, $psr7Response);

        /**
         * Action: intercept_http_response
         * Description: Intercept Psr7 HTTP response.
         * @param ResponseInterface
         */
        $psr7Response = Hooks::getInstance()->apply_filters('intercept_http_response', $psr7Response);

        return $psr7Response;
    }
}