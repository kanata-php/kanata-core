<?php

namespace Kanata\Services\Traits;

use Error;
use Exception;
use Kanata\Models\WsChannel;

trait ChannelsPersistence
{
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
}
