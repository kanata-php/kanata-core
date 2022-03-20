<?php

namespace Kanata\Http\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;

class DocumentationController
{
    public function index(Request $request, Response $response)
    {
        return view($response, 'core::docs/index');
    }
}