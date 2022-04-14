<?php

use Kanata\Drivers\SessionTable;
use Kanata\Services\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

if (! function_exists('set_session')) {
    /**
     * Set session data in request.
     *
     * @return void
     */
    function set_session(ServerRequestInterface &$request, array $data): void
    {
        $request_data = $request->session;

        $session_data  = SessionTable::getInstance()->get($request_data['id']) ?? [];

        $session_data = array_merge($session_data, $data);

        SessionTable::getInstance()->set($request_data['id'], $session_data);
    }
}

if (! function_exists('get_session')) {
    /**
     * Get session data in request.
     *
     * @return mixed
     */
    function get_session(ServerRequestInterface &$request, ?string $key = null, $default = null): mixed
    {
        $data = $request->session;

        $session_data  = SessionTable::getInstance()->get($data['id']) ?? [];

        return array_get($session_data, $key, $default);
    }
}

if (! function_exists('clear_session')) {
    /**
     * Clear session data in request.
     * 
     * @important only removes keys at the root of the session's array.
     * @return void
     */
    function clear_session(ServerRequestInterface &$request, ?string $key = null): void
    {
        $request_data = $request->session;

        if (null === $key) {
            SessionTable::getInstance()->set($request_data['id'], []);
            return;
        }

        $session_data  = SessionTable::getInstance()->get($request_data['id']);
        
        if (!isset($session_data[$key])) {
            return;
        }

        unset($session_data[$key]);

        SessionTable::getInstance()->set($request_data['id'], $session_data);
    }
}

if (! function_exists('set_form_session')) {
    /**
     * Set session data in request.
     *
     * @return void
     */
    function set_form_session(ServerRequestInterface &$request, array $data): void
    {
        set_session($request, ['form' => [$request->getUri()->getPath() => $data]]);
    }
}

if (! function_exists('get_form_session')) {
    /**
     * Get form session data in request.
     *
     * @return array
     */
    function get_form_session(ServerRequestInterface $request): array
    {
        return get_session($request, 'form.' . $request->getUri()->getPath(), []);
    }
}

if (! function_exists('clear_form_session')) {
    /**
     * Clear form session data in current request path.
     *
     * @return void
     */
    function clear_form_session(ServerRequestInterface $request): void
    {
        $data = $request->session;

        $session_data  = SessionTable::getInstance()->get($data['id']);

        $form = $session_data['form'];
        unset($form[$request->getUri()->getPath()]);
        $session_data['form'] = $form;

        SessionTable::getInstance()->set($data['id'], $session_data);
    }
}

if (! function_exists('set_flash_message')) {
    /**
     * Create a flash message.
     *
     * @return void
     */
    function set_flash_message(ServerRequestInterface $request, array $message): void
    {
        $data = $request->session;

        $session_data  = SessionTable::getInstance()->get($data['id']);

        if (isset($session_data['flash-message'])) {
            $message = array_merge($message, $session_data['flash-message']);
        }

        set_session($request, ['flash-message' => $message]);
    }
}

if (! function_exists('get_flash_message')) {
    /**
     * Consume a flash message.
     *
     * @return mixed
     */
    function get_flash_message(ServerRequestInterface $request)
    {
        $data = $request->session;

        $session_data  = SessionTable::getInstance()->get($data['id']);

        $result = null;
        if (isset($session_data['flash-message'])) {
            $result = $session_data['flash-message'];
            unset($session_data['flash-message']);
            SessionTable::getInstance()->set($data['id'], $session_data);
        }
        
        return $result;
    }
}

if (! function_exists('clear_flash_message')) {
    /**
     * Clear flash message.
     *
     * @return void
     */
    function clear_flash_message(ServerRequestInterface $request): void
    {
        $data = $request->session;

        $session_data  = SessionTable::getInstance()->get($data['id']);
        unset($session_data['flash-message']);
        SessionTable::getInstance()->set($data['id'], $session_data);
    }
}