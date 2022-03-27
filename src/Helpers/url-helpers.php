<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

if (! function_exists('back')) {
    /**
     * Verify if the mysql db table exists.
     *
     * @param Request $request
     * @param Request $response
     * @param array $query
     */
    function back(Request $request, Response $response, array $queryParams)
    {
        $referer = current(explode('?', current($request->getHeader('referer'))));

        if (empty($referer)) {
            throw new Exception('There is no referer at request object.');
        }

        $query = '';
        if (!empty($queryParams)) {
            $query = '?' . http_build_query($queryParams);
        }

        return redirect($response, $referer . $query);
    }
}

if (! function_exists('redirect')) {
    /**
     * Verify if the mysql db table exists.
     *
     * @param Request $request
     * @param Request $response
     * @param array $query
     */
    function redirect(Response $response, string $url)
    {
        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}

if (! function_exists('base_url')) {
    /**
     * Get base url of the system.
     *
     * @return string
     * @throws Exception
     */
    function base_url(): string
    {
        $url = config('app.app-url');

        if (null === $url) {
            throw new Exception('No base_url found!');
        }

        $protocol = env('HTTP_SERVER_SSL') ? 'https://' : 'http://';

        return $protocol . $url;
    }
}

if (! function_exists('route')) {
    /**
     * Prepare url for route.
     *
     * @param string $name
     * @param array $params
     * @param array $urlQuery
     * @return string
     * @throws Exception
     */
    function route(string $name, array $params = [], array $urlQuery = []): string
    {
        global $app;
        $routeParser = $app->getRouteCollector()->getRouteParser();
        return $routeParser->urlFor($name, $params, $urlQuery);
    }
}
