<?php

namespace Kanata\Services;

use Kanata\Exceptions\ErrorHandler;
use Kanata\Exceptions\ErrorRenderer;
use Kanata\Http\Controllers\DocumentationController;
use Kanata\Http\Middlewares\RequestResolutionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteCollectorProxy;
use voku\helper\Hooks;

class Routes
{
    public static function start(): void
    {
        global $app;

        // ------------------------------------------------
        // Error handling : BEGIN
        // ------------------------------------------------

        $app->addRoutingMiddleware();
        $kanataErrorHandler = new ErrorHandler($app->getCallableResolver(), $app->getResponseFactory());

        /**
         * Add Error Middleware
         *
         * @param bool                  $displayErrorDetails -> Should be set to false in production
         * @param bool                  $logErrors -> Parameter is passed to the default ErrorHandler
         * @param bool                  $logErrorDetails -> Display error details in error log
         * @param LoggerInterface|null  $logger -> Optional PSR-3 Logger
         *
         * Note: This middleware should be added last. It will not handle any exceptions/errors
         * for middleware added after it.
         */
        $errorMiddleware = $app->addErrorMiddleware(true, true, true);

        $errorMiddleware->setDefaultErrorHandler($kanataErrorHandler);

        $errorHandler = $errorMiddleware->getDefaultErrorHandler();
        $errorHandler->registerErrorRenderer('text/html', ErrorRenderer::class);

        // ------------------------------------------------
        // Error handling : END
        // ------------------------------------------------

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