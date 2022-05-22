<?php

namespace Kanata\Services;

use Kanata\Interfaces\EventInterface;
use Swoole\Timer;

class Events
{
    public static function dispatch(EventInterface $event): void
    {
        Timer::after(1, [$event, 'handle']);
    }

    public static function dispatchNow(EventInterface $event): void
    {
        $event->handle();
    }
}
