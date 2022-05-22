<?php

use Kanata\Interfaces\EventInterface;
use Kanata\Services\Events;

if (! function_exists('dispatch_event')) {
    /**
     * Dispatch an event to this app instance.
     *
     * @param EventInterface $event
     * @param bool $sync
     * @return void
     */
    function dispatch_event(EventInterface $event, bool $sync = false): void {
        if (!$sync) {
            Events::dispatch($event);
            return;
        }

        Events::dispatchNow($event);
    }
}

if (! function_exists('add_event_listener')) {
    /**
     * Add event listener.
     *
     * @param EventInterface $event
     * @param bool $sync
     * @return void
     */
    function add_event_listener(string $event, callable $callback): void {
        Events::addEventObserver($event, $callback);
    }
}
