<?php

use Kanata\Services\WebSocketCommunication;
use Kanata\Services\WebSocketPersistence;

if (! function_exists('socket_communication')) {
    /**
     * Get websocket communication instance.
     *
     * @return WebSocketCommunication
     */
    function socket_communication(): WebSocketCommunication
    {
        return container()->socket_communication;
    }
}

if (! function_exists('socket_persistence')) {
    /**
     * Get websocket persistence instance.
     *
     * @return WebSocketPersistence
     */
    function socket_persistence(): WebSocketPersistence
    {
        return container()->socket_persistence;
    }
}
