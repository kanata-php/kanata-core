<?php

namespace Kanata\Http\Middlewares;

use Closure;
use Kanata\Http\Middlewares\Interfaces\RouteMiddlewareInterface;
use League\Pipeline\PipelineBuilder;
use League\Pipeline\StageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\RouteInterface;
use Slim\Psr7\Headers;
use Slim\Routing\RouteContext;
use voku\helper\Hooks;

class RequestResolutionMiddleware implements RouteMiddlewareInterface
{

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws HttpNotFoundException
     */
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $this->getCurrentRoute($request);
        $callable = $route->getCallable();
        $request = $this->prepareRequest($request, $callable);
        return $handler->handle($request);
    }

    /**
     * Here we convert incoming request object into the Request object expected by the
     * callable in the other side (controller of closure).
     *
     * @param ServerRequestInterface $request
     * @param mixed $callable Here we will get closure or controllers
     * @return ServerRequestInterface
     * @throws \ReflectionException
     */
    private function prepareRequest(ServerRequestInterface $request, mixed $callable): ServerRequestInterface {
        if (is_a($callable, Closure::class)) {
            $reflection = new ReflectionFunction($callable);
        } else {
            $reflection = new ReflectionMethod(array_get($callable, 0), array_get($callable, 1));
        }

        $parameters = current(array_filter($reflection->getParameters(), function ($item) {
            $type = $item->getType()->getName();
            if (class_exists($type)) {
                $class = new ReflectionClass($type);
                return null !== array_get($class->getInterfaces(), RequestInterface::class);
            }
            return false;
        }));

        if (!is_bool($parameters)) {
            $newRequest = new ReflectionClass($parameters->getType()->getName());

            /** @var ServerRequestInterface $request */
            $request = $newRequest->newInstanceArgs([
                $request->getMethod(),
                $request->getUri(),
                new Headers($request->getHeaders()),
                $request->getCookieParams(),
                $request->getServerParams(),
                $request->getBody(),
                $request->getUploadedFiles(),
            ]);

            $request = $this->processRequest($request);
        }

        return $request;
    }

    /**
     * Here we execute some standard workflow.
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    private function processRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        /**
         * Filter: request_workflow
         * Description: Customize request's workflow adding processing stages.
         * Expected return: StageInterface[]
         * @param StageInterface[] $stages
         */
        $stages = Hooks::getInstance()->apply_filters('request_workflow', []);

        $pipelineBuilder = new PipelineBuilder;
        foreach ($stages as $stage) {
            $pipelineBuilder->add($stage);
        }
        $pipeline = $pipelineBuilder->build();
        $request = $pipeline->process($request);

        return $request;
    }

    /**
     * @param ServerRequestInterface $request
     * @return RouteInterface
     * @throws HttpNotFoundException
     */
    private function getCurrentRoute(ServerRequestInterface $request): RouteInterface
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        // return NotFound for non-existent route
        if (empty($route)) {
            throw new HttpNotFoundException($request);
        }

        return $route;
    }
}