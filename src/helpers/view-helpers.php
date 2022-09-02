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

        if ('core' === substr($view, 0, 4)) {
            $html = container()->view->render($view, $params);
            $response->getBody()->write($html);
            return $response->withStatus($status);
        }

        // get core template version from current view
        $templateParts = array_get(explode(DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR, container()->view->path($view)), '1');
        $coreTemplate = 'core::vendors' . DIRECTORY_SEPARATOR .
            pathinfo($templateParts, PATHINFO_DIRNAME) .
            DIRECTORY_SEPARATOR .
            pathinfo($templateParts, PATHINFO_FILENAME);

        if (container()->view->exists($coreTemplate)) {
            $html = container()->view->render($coreTemplate, $params);
            $response->getBody()->write($html);
            return $response->withStatus($status);
        }

        $html = container()->view->render($view, $params);
        $response->getBody()->write($html);
        return $response->withStatus($status);
    }
}