<?php

namespace Kanata\Services;

use Ramsey\Uuid\Uuid;
use voku\helper\Hooks;
use Chocookies\Cookies;
use Kanata\Drivers\SessionTable;
use Kanata\Interfaces\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class Session implements SessionInterface
{
    public static function startSession(Request $request): array
    {
        $current_session = self::getCurrentSession($request);

        return $current_session;
    }

    public static function addCookiesToResponse(Request $request, ResponseInterface &$response): void
    {
        $data = $request->session;
        $session_data = SessionTable::getInstance()->get($data['id']);

        $cookies = array_only(
            $session_data,
            /**
             * Action: add_cookie_keys
             * Description: Set keys to be added when building cookies.
             * @param array
             */
            Hooks::getInstance()->apply_filters('add_cookie_keys', ['id'])
        );

        Cookies::setCookie(
            $response,
            SessionTable::SESSION_KEY,
            self::encodeCookie($cookies)
        );
    }

    private static function getCurrentSession(Request $request): array
    {
        $session_table = SessionTable::getInstance();

        $session_data = Cookies::getCookie($request, SessionTable::SESSION_KEY);

        if (null !== $session_data) {
            $session_data = self::parseCookie($session_data);
        }

        if (empty($session_data)) {
            $session_data['id'] = Uuid::uuid4()->toString();
        }

        if (!$session_table->has($session_data['id'])) {
            $session_table->set($session_data['id'], $session_data);
        }

        $current_session = $session_table->get($session_data['id']);

        return $current_session ?? [];
    }

    private static function parseCookie(string $data)
    {
        $data = str_replace(config('app.session-key'), '', $data);
        return json_decode(base64_decode($data), true);
    }

    private static function encodeCookie(array $data): string
    {
        $data = json_encode($data);
        return config('app.session-key') . base64_encode($data);
    }
}
