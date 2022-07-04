<?php

namespace Kanata\Http\Middlewares;

use Exception;
use Kanata\Http\Middlewares\Interfaces\HttpMiddlewareInterface;
use League\Pipeline\Pipeline;
use League\Pipeline\PipelineBuilder;
use League\Pipeline\PipelineInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use voku\helper\Hooks;

/**
 * Middleware for Swoole Request side.
 */
class CoreMiddleware
{
    protected $callback;
    protected PipelineInterface $pipeline;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;

        $pipelineBuilder = new PipelineBuilder();

        /**
         * Action: swoole_http_middleware
         * Description: Allows HTTP middleware registration on Swoole Request.
         * @param callable[] $middlewares
         */
        $middlewares = Hooks::getInstance()->apply_filters('swoole_http_middleware', []);

       foreach ($middlewares as $middleware) {
           if (!is_a($middleware, HttpMiddlewareInterface::class)) {
               throw new Exception('Invalid HTTP middleware: ' . get_class($middleware));
           }

           $pipelineBuilder = $pipelineBuilder->add($middleware);
       }

       $this->pipeline = $pipelineBuilder->build();
    }

    public function __invoke(Request $request, Response $response)
    {
        [$request, $response] = $this->pipeline->process([$request, $response]);

        call_user_func($this->callback, $request, $response);
    }
}