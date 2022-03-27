<?php

namespace Kanata\Http\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserAuthorization\Services\Cookies;
use voku\helper\Hooks;

class AdminController extends Controller
{
    public function index(Request $request, Response $response)
    {
        return view($response, 'core::admin/dashboard', [
            'is_logged' => is_logged($request),
        ]);
    }
}