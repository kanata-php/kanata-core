<?php

if (! function_exists('register_queue')) {
    /**
     * Register a new callback to a message in the AMQP service.
     *
     * @param string $queue The name of the queue.
     * @param string $exchange The name of the exchange.
     * @param string $option The command to run on terminal for long-running service.
     * @param mixed $callback Function that receives the message.
     * @param string $routingKey The name of the routing key. (default: '')
     * @return void
     */
    function register_queue(string $queue, string $exchange, string $option, mixed $callback, string $routingKey = '')
    {
        add_filter('queues', function ($queues) use ($queue, $exchange, $option, $callback, $routingKey) {
            $queues[$queue] = [
                'exchange' => $exchange,
                'queue' => $queue,
                'routing_key' => $routingKey,
                'option' => $option,
                'callback' => $callback,
            ];

            return $queues;
        });
    }
}
