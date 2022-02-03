<?php

namespace Kanata\Services;

use Doctrine\Common\Cache\FilesystemCache;
use League\Flysystem\Adapter\Local;
use League\Plates\Engine;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use League\Flysystem\Filesystem as Flysystem;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use voku\helper\Hooks;

class Dependencies
{
    public static function start (): void
    {
        $container = container();

        /**
         * -----------------------------------------------------------
         * Utilities Section
         * -----------------------------------------------------------
         */

        $container['logger'] = function ($c) {
            $logger = new Logger('kanata-logger');
            $file_handler = new StreamHandler(storage_path() . 'logs/app.log');
            $logger->pushHandler($file_handler);
            return $logger;
        };

        $container['view'] = new Engine();
        $container['view']->addFolder('core', template_path());

        $container['cache'] = function ($c) {
            $cache = new FilesystemCache(storage_path() . 'cache/');
            return $cache;
        };

        /**
         * -----------------------------------------------------------
         * Filesystem
         * -----------------------------------------------------------
         */

        $container['filesystem'] = function ($c) {
            $adapter = new Local(__DIR__ . '/../');
            return new Flysystem($adapter);
        };

        /**
         * -----------------------------------------------------------
         * WebSockets Section
         * -----------------------------------------------------------
         */

        $container['socket_persistence'] = function ($c) {
            /**
             * Here you can choose a different websocket persistence implementation.
             *
             * Interface: \Conveyor\SocketHandlers\Interfaces\PersistenceInterface
             */
            return Hooks::getInstance()->apply_filters(
                'socket_persistence',
                new WebSocketPersistence
            );
        };

        $container['socket_communication'] = function ($c) {
            /**
             * Here you can choose a different websocket communication implementation.
             *
             * Interface: \Kanata\Interfaces\WebSocketCommunicationInterface
             */
            return Hooks::getInstance()->apply_filters(
                'socket_communication',
                new WebSocketCommunication(),
                container()
            );
        };

        /**
         * -----------------------------------------------------------
         * AMQP Section
         * -----------------------------------------------------------
         */

        $container['amqp'] = function ($c) {
            return new AMQPStreamConnection(
                QUEUE_SERVER_HOST,
                QUEUE_SERVER_PORT,
                QUEUE_SERVER_USER,
                QUEUE_SERVER_PASSWORD
            );
        };

    }
}
