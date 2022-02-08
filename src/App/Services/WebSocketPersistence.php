<?php

namespace Kanata\Services;

use Kanata\Models\WsChannel;
use Kanata\Models\WsListener;
use Kanata\Services\Traits\SQLiteTrait;
use Conveyor\SocketHandlers\Interfaces\PersistenceInterface;

class WebSocketPersistence implements PersistenceInterface
{
    use SQLiteTrait;

    public function connect(int $fd, string $channel): void
    {
        $this->disconnect($fd);
        WsChannel::getInstance()->createRecord([
            'fd' => $fd,
            'channel' => $channel,
        ]);
    }

    public function disconnect(int $fd): void
    {
        WsChannel::getInstance()->where('fd', '=', $fd)->delete();
    }

    public function getAllConnections(): array
    {
        $channels = WsChannel::getInstance()->findAll()->asArray();

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
        WsListener::getInstance()->createRecord([
            'fd' => $fd,
            'action' => $action,
        ]);
    }

    public function getListener(int $fd): array
    {
        $listeners =  WsListener::getInstance()->findAll()->asArray();

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

    /**
     * @return array Format: [fd => [listener1, listener2, ...]]
     */
    public function getAllListeners(): array
    {
        return WsListener::getInstance()->findAll()->asArray();
    }

    public function stopListener(int $fd, string $action)
    {
        return WsListener::getInstance()
            ->where('fd', '=', $fd)
            ->where('action', '=', $action)
            ->delete();
    }

    public function cleanListeners()
    {
        return WsListener::getInstance()->findAll()->delete();
    }
}
