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

        return array_map(fn ($item) => $item['channel'], $channels);
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
        return WsListener::getInstance()->where('fd', '=', $fd)->asArray();
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
