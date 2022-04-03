<?php

use Kanata\Drivers\SessionTable;
use Kanata\Services\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

if (! function_exists('set_form_session')) {
    /**
     * Set session data in request.
     *
     * @return void
     */
    function set_form_session(ServerRequestInterface &$request, array $data): void
    {
        $request_data = $request->session;

        $session_data  = SessionTable::getInstance()->get($request_data['id']);

        if (!isset($session_data['form'])) {
            $session_data['form'] = [];
        }
        $session_data['form'][$request->getUri()->getPath()] = $data;

        SessionTable::getInstance()->set($request_data['id'], $session_data);
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
        $data = $request->session;

        $path = $request->getUri()->getPath();

        $session_data  = SessionTable::getInstance()->get($data['id']);

        if (
            !isset($session_data['form'])
            || !isset($session_data['form'][$path])
        ) {
            return [];
        }

        return $session_data['form'][$path];
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

        $session_data['flash-message'] = $message;

        SessionTable::getInstance()->set($data['id'], $session_data);
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