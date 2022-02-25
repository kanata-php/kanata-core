<?php

namespace Kanata\Services;

use Kanata\Interfaces\WebSocketCommunicationInterface;
use Kanata\Models\WsCommunication;

class WebSocketCommunication implements WebSocketCommunicationInterface
{
    public function set(string $action, string $data): void
    {
        WsCommunication::create([
            'action' => $action,
            'data' => $data,
        ]);
    }

    public function get(string $action): null|array
    {
        return WsCommunication::where('action', '=', $action)->get()?->toArray();
    }

    public function clean(string $action): void
    {
        WsCommunication::where('action', '=', $action)->delete();
    }

    public function delete(int $id): bool
    {
        if (WsCommunication::find($id)?->delete()) {
            return true;
        }

        return false;
    }
}

