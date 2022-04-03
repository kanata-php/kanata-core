<?php

use Psr\Http\Message\ResponseInterface as Response;

if (! function_exists('view')) {
    /**
     * Render view for route.
     *
     * @param Response $response
     * @param string $view
     * @param array $params
     * @param int $status
     * @return Response
     */
    function view(Response $response, string $view, array $params = [], int $status = 200): Response
    {
        $html = container()->view->render($view, $params);
        $response->getBody()->write($html);
        return $response->withStatus($status);
    }
}