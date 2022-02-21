<?php

namespace Kanata\Services\Traits;

use Error;
use Exception;
use Kanata\Models\WsListener;

trait ListenersPersistence
{
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

    public function stopListener(int $fd, string $action): bool
    {
        try {
            return WsListener::getInstance()
                ->where('fd', '=', $fd)
                ->where('action', '=', $action)
                ->delete();
        } catch (Exception|Error $e) {
            // --
        }

        return false;
    }

    public function stopListenersForFd(int $fd): bool
    {
        try {
            return WsListener::getInstance()
                ->where('fd', '=', $fd)
                ->delete();
        } catch (Exception|Error $e) {
            // --
        }

        return false;
    }

    public function cleanListeners(): bool
    {
        try {
            return WsListener::getInstance()->findAll()->delete();
        } catch (Exception|Error $e) {
            // --
        }

        return false;
    }
}
