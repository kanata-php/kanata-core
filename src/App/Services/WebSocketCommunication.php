<?php

namespace Kanata\Services;

use Kanata\Interfaces\WebSocketCommunicationInterface;
use Kanata\Models\WsCommunication;

class WebSocketCommunication implements WebSocketCommunicationInterface
{
    public function set(string $action, string $data): void
    {
        WsCommunication::getInstance()->createRecord([
            'action' => $action,
            'data' => $data,
        ]);
    }

    public function get(string $action): array
    {
        return WsCommunication::getInstance()->where('action', '=', $action)->findAll()->asArray();
    }

    public function clean(string $action): void
    {
        WsCommunication::getInstance()->where('action', '=', $action)->delete();
    }
}

