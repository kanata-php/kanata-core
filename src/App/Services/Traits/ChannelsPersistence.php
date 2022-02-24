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
            WsChannel::create([
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
            WsChannel::where('fd', '=', $fd)->first()->delete();
        } catch (Exception|Error $e) {
            // --
        }
    }

    public function getAllConnections(): array
    {
        try {
            $channels = WsChannel::all()->toArray();
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
