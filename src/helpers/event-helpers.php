<?php

use Kanata\Services\Servers;

if (! function_exists('get_events_list')) {
    /**
     * Return a list of all events existent in this app instance.
     *
     * @return array
     */
    function get_events_list(): array {
        /**
         * Filter: server_events
         * Description: List of available events in the system.
         * Expected return: array
         * @param array $events [ 'event_key' => $callback ]
         */
        return apply_filters('server_events', [[]]);
    }
}

if (! function_exists('register_event')) {
    /**
     * Register an event to this app instance.
     *
     * @param string $eventKey
     * @param callable $callback
     * @return void
     */
    function register_event(string $eventKey, callable $callback): void {
        add_filter('server_events', function ($events) use ($eventKey, $callback) {
            if (!isset($events[$eventKey])) {
                $events[$eventKey] = [];
            }
            $events[$eventKey][] = $callback;
            return $events;
        });
    }
}

if (! function_exists('dispatch_event')) {
    /**
     * Dispatch an event to this app instance.
     *
     * @param string $eventKey
     * @param string $data
     * @return void
     */
    function dispatch_event(string $eventKey, string $data): void {
        $table = container()->get(Servers::EVENTS_TABLE);
        $table->set(count($table), [
            'event_key' => $eventKey,
            'event_data' => $data,
            'timestamp' => (new DateTime)->getTimestamp(),
        ]);
    }
}