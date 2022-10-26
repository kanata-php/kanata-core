<?php

use Conveyor\SocketHandlers\Interfaces\ChannelPersistenceInterface;
use Conveyor\SocketHandlers\Interfaces\ListenerPersistenceInterface;
use Conveyor\SocketHandlers\Interfaces\UserAssocPersistenceInterface;
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

if (! function_exists('ws_listener_persistence')) {
    /**
     * Get websocket listener persistence instance.
     *
     * @return ListenerPersistenceInterface
     */
    function ws_listener_persistence(): ListenerPersistenceInterface
    {
        return container()->ws_listener_persistence;
    }
}

if (! function_exists('ws_assoc_persistence')) {
    /**
     * Get websocket user association persistence instance.
     *
     * @return UserAssocPersistenceInterface
     */
    function ws_assoc_persistence(): UserAssocPersistenceInterface
    {
        return container()->ws_assoc_persistence;
    }
}

if (! function_exists('ws_channel_persistence')) {
    /**
     * Get websocket channel persistence instance.
     *
     * @return ChannelPersistenceInterface
     */
    function ws_channel_persistence(): ChannelPersistenceInterface
    {
        return container()->ws_channel_persistence;
    }
}
