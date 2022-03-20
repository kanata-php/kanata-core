<?php

namespace Kanata\Services;

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use voku\helper\Hooks;
use Kanata\Exceptions\ErrorHandler;
use Kanata\Http\Controllers\LoginController;
use Kanata\Http\Controllers\RegisterController;
use Kanata\Http\Controllers\AdminController;
use Kanata\Http\Controllers\DocumentationController;

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

        /**
         * Filter: routes
         * Description: Important for Routes specification via plugins.
         * Expected return: SocketHandlerInterface
         * @param App $app
         */
        $app = Hooks::getInstance()->apply_filters('routes', $app);

        $app->group('', function (RouteCollectorProxy $group) {
            $group->get('/', function (Request $request, Response $response) {
                return view($response, 'core::home');
            })->setName('home');

            $group->get('/docs', [DocumentationController::class, 'index'])->setName('login');

            $group->get('/admin', [AdminController::class, 'index'])->setName('admin');
        })->add($errorMiddleware);
    }
}