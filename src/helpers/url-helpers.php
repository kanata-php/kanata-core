<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

if (! function_exists('previous_url')) {
    /**
     * Get the previous url.
     *
     * @param Request $request
     * @param array $query
     * @return string
     */
    function previous_url(Request $request, array $queryParams): string
    {
        if ($request->hasHeader('referer')) {
            $referer = current(explode('?', current($request->getHeader('referer'))));
        } else {
            $referer = route('home', [], [], $request);
        }

        if (empty($referer)) {
            throw new Exception('There is no referer at request object.');
        }

        $query = '';
        if (!empty($queryParams)) {
            $query = '?' . http_build_query($queryParams);
        }

        return $referer . $query;
    }
}

if (! function_exists('back')) {
    /**
     * Get the previous url.
     *
     * @param Request $request
     * @param Response $response
     * @param array $query
     * @return Response
     */
    function back(Request $request, Response $response, array $queryParams = []): Response
    {
        return redirect($response, previous_url($request, $queryParams));
    }
}

if (! function_exists('redirect')) {
    /**
     * Get the redirection url.
     *
     * @param Request $response
     * @param string $url
     * @return Response
     */
    function redirect(Response $response, string $url): Response
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
    function route(string $name, array $params = [], array $urlQuery = [], ?Request $request = null): string
    {
        global $app;
        $routeParser = $app->getRouteCollector()->getRouteParser();
        if (null !== $request) {
            return $routeParser->fullUrlFor($request->getUri(), $name, $params, $urlQuery);
        } else {
            return $routeParser->urlFor($name, $params, $urlQuery);
        }
    }
}
