<?php

namespace Kanata\Services;

use Kanata\Exceptions\ErrorHandler;
use Kanata\Http\Controllers\DocumentationController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use voku\helper\Hooks;

class Routes
{
    public static function start(): void
    {
        global $app;

        // Error handling.
        $app->addRoutingMiddleware();
        $errorMiddleware = $app->addErrorMiddleware(true, true, true);
        $errorHandler = $errorMiddleware->getDefaultErrorHandler();
        $errorHandler->registerErrorRenderer('text/html', ErrorHandler::class);

        $app->group('', function (RouteCollectorProxy $group) {
            /**
             * Filter: routes
             * Description: Important for Routes specification via plugins.
             * Expected return: SocketHandlerInterface
             * @param RouteCollectorProxy $group
             */
            $group = Hooks::getInstance()->apply_filters('routes', $group);

            $group->get('/', function (Request $request, Response $response) {
                return view($response, 'core::home', []);
            })->setName('home');

            $group->get('/docs', [DocumentationController::class, 'index'])->setName('docs');
        })->add($errorMiddleware);
    }
}