<?php

namespace Kanata\Services;

use Kanata\Models\WsChannel;
use Kanata\Models\WsListener;
use Kanata\Services\Traits\SQLiteTrait;
use Conveyor\SocketHandlers\Interfaces\PersistenceInterface;
use Exception;
use Error;

class WebSocketPersistence implements PersistenceInterface
{
    use SQLiteTrait;

    public function connect(int $fd, string $channel): void
    {
        $this->disconnect($fd);
        try {
            WsChannel::getInstance()->createRecord([
                'fd' => $fd,
                'channel' => $channel,
            ]);
        } catch(Exception|Error $e) {
            // --
        }
    }

    public function disconnect(int $fd): void
    {
        try {
            WsChannel::getInstance()->where('fd', '=', $fd)->delete();
        } catch (Exception|Error $e) {
            // --
        }
    }

    public function getAllConnections(): array
    {
        try {
            $channels = WsChannel::getInstance()->findAll()->asArray();
        } catch (Exception|Error $e) {
            return [];
        }

        if (empty($channels)) {
            return [];
        }

        $connections = [];
        foreach ($channels as $channel) {
            $connections[$channel['fd']] = $channel['channel'];
        }

        return $connections;
    }

    public function listen(int $fd, string $action): void
    {
        try {
            WsListener::getInstance()->createRecord([
                'fd' => $fd,
                'action' => $action,
            ]);
        } catch (Exception|Error $e) {
            // --
        }
    }

    public function getListener(int $fd): array
    {
        return WsListener::getInstance()->where('fd', '=', $fd)->asArray();
    }

    /**
     * @return array Format: [fd => [listener1, listener2, ...]]
     */
    public function getAllListeners(): array
    {
        try {
            $listeners = WsListener::getInstance()->findAll()->asArray();
        } catch (Exception|Error $e) {
            return [];
        }

        if (empty($listeners)) {
            return [];
        }

        $listenersArray = [];
        foreach ($listeners as $listener) {
            if (!isset($listenersArray[$listener['fd']])) {
                $listenersArray[$listener['fd']] = [];
            }

            if (!in_array($listener['action'], $listenersArray[$listener['fd']])) {
                $listenersArray[$listener['fd']][] = $listener['action'];
            }
        }

        return $listenersArray;
    }

    public function stopListener(int $fd, string $action)
    {
        try {
            return WsListener::getInstance()
                ->where('fd', '=', $fd)
                ->where('action', '=', $action)
                ->delete();
        } catch (Exception|Error $e) {
            // --
        }
    }

    public function cleanListeners()
    {
        try {
            return WsListener::getInstance()->findAll()->delete();
        } catch (Exception|Error $e) {
            // --
        }
    }
}
