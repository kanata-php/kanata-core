<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use voku\helper\Hooks;

if (! function_exists('is_logged')) {
    /**
     * Identifies authorization in the request.
     *
     * @param Request $request
     * @return bool
     */
    function is_logged(Request $request): bool
    {
        /**
         * Action: is_logged
         * Description: Important for WebSocket server mode.
         * Expected return: bool
         * @param Request $request
         */
        return Hooks::getInstance()->apply_filters(
            'is_logged',
            true,
            $request
        );
    }
}
