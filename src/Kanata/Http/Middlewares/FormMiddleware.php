<?php

namespace Kanata\Http\Middlewares;

use voku\helper\Hooks;
use Kanata\Exceptions\UnauthorizedException;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * This middleware is useful to preserve form fields filled when there is an
 * error that the user needs to fix, on that case, the user will have the 
 * values back with the form.
 */
class FormMiddleware
{
    /**
     * @param Request $request
     * @return Request
     * @throws UnauthorizedException
     */
    public function __invoke(Request $request): Request
    {
        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            /**
             * Action: http_form_not_allowed_in_session
             * Description: Filter form data that can be stored in the session.
             * @param array $data
             */
            $http_form_not_allowed_in_session = Hooks::getInstance()->apply_filters('http_form_not_allowed_in_session', ['password', 'password_confirmation']);

            $data = [];
            foreach ($request->getParsedBody() as $key => $value) {
                if (!in_array($key, $http_form_not_allowed_in_session)) {
                    $data[$key] = $value;
                }
            }

            set_form_session($request, $data);
        }

        return $request;
    }
}
