<?php

namespace Kanata\Services;

use Kanata\Exceptions\ErrorHandler;
use Kanata\Exceptions\ErrorRenderer;
use Kanata\Http\Controllers\DocumentationController;
use Kanata\Http\Middlewares\RequestResolutionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Slim\Routing\RouteCollectorProxy;
use voku\helper\Hooks;

class Routes
{
    public static function start(): void
    {
        global $app;

        // Error handling.
        $app->addRoutingMiddleware();
        $kanataErrorHandler = new ErrorHandler($app->getCallableResolver(), $app->getResponseFactory());
        $errorMiddleware = $app->addErrorMiddleware(true, true, true);
        $errorMiddleware->setDefaultErrorHandler($kanataErrorHandler);

        $errorHandler = $errorMiddleware->getDefaultErrorHandler();
        $errorHandler->registerErrorRenderer('text/html', ErrorRenderer::class);

        $app->group('', function (RouteCollectorProxy $group) {
            /**
             * Filter: routes
             * Description: Important for Routes specification via plugins.
             * Expected return: SocketHandlerInterface
             * @param RouteCollectorProxy $group
             */
            $group = Hooks::getInstance()->apply_filters('routes', $group);

            // This allows the home route to be overwritten - in case it is already
            // registered, we avoid registering.
            try {
                $group->getRouteCollector()->getNamedRoute('home');
            } catch (RuntimeException $e) {
                $group->get('/', function (Request $request, Response $response) {
                    return view($response, 'core::home', []);
                })->setName('home');
            }

            try {
                $group->getRouteCollector()->getNamedRoute('docs');
            } catch (RuntimeException $e) {
                $group->get('/docs', [DocumentationController::class, 'index'])->setName('docs');
            }
        })
            ->add($errorMiddleware)
            ->add(new RequestResolutionMiddleware);
    }
}