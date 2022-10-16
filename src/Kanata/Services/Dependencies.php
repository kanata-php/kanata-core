<?php

namespace Kanata\Services;

use Conveyor\SocketHandlers\Interfaces\ChannelPersistenceInterface;
use Conveyor\SocketHandlers\Interfaces\ListenerPersistenceInterface;
use Conveyor\SocketHandlers\Interfaces\UserAssocPersistenceInterface;
use Doctrine\Common\Cache\FilesystemCache;
use Kanata\Drivers\DbCapsule;
use Kanata\Repositories\PluginRepository;
use Kanata\Services\Traits\AssociationsPersistence;
use Kanata\Services\Traits\ChannelsPersistence;
use Kanata\Services\Traits\ListenersPersistence;
use League\Flysystem\Adapter\Local;
use League\Plates\Engine;
use Monolog\Formatter\LogstashFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
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

        $container['view'] = function($c) {
            $engine = new Engine();

            /**
             * Action: view_folders
             * Description: Here you can add new folders for views in alternative folders.
             * Expected return: array
             * @param array $view_folders
             */
            $view_folders = Hooks::getInstance()->apply_filters(
                'view_folders',
                ['core' => template_path()]
            );

            foreach ($view_folders as $key => $folder) {
                $engine->addFolder($key, $folder);
            }

            return $engine;
        };

        $container['cache'] = function ($c) {
            $cache = new FilesystemCache(storage_path() . 'cache/');
            return $cache;
        };

        $container['db'] = function () {
            $capsule = new DbCapsule;
            $capsule->addConnection([
                'driver' => DB_DRIVER,
                'host' => DB_HOST,
                'port' => DB_PORT,
                'database' => DB_DATABASE,
                'username' => DB_USERNAME,
                'password' => DB_PASSWORD,
                'charset' => DB_CHARSET,
                'collation' => DB_COLLATION,
                'prefix' => DB_PREFIX,
            ]);
            $capsule->setAsGlobal();
            $capsule->bootEloquent();
            return $capsule;
        };

        $container['plugin-repository'] = function () {
            return new PluginRepository;
        };

        $container['helpers'] = function () {
            return new Helpers;
        };

        /**
         * -----------------------------------------------------------
         * Filesystem
         * -----------------------------------------------------------
         */

        $container['filesystem'] = function ($c) {
            $adapter = new Local(ROOT_FOLDER);
            return new Flysystem($adapter);
        };

        /**
         * -----------------------------------------------------------
         * WebSockets Section
         * -----------------------------------------------------------
         */

        $container['ws_channel_persistence'] = function ($c) {
            /**
             * Action: ws_channel_persistence
             * Description: Here you can choose a different websocket channel persistence implementation.
             * Expected return: \Conveyor\SocketHandlers\Interfaces\ChannelPersistenceInterface
             */
            return Hooks::getInstance()->apply_filters(
                'ws_channel_persistence',
                new ChannelsPersistence
            );
        };

        $container['ws_assoc_persistence'] = function ($c) {
            /**
             * Action: ws_assoc_persistence
             * Description: Here you can choose a different websocket user association persistence implementation.
             * Expected return: \Conveyor\SocketHandlers\Interfaces\UserAssocPersistenceInterface
             */
            return Hooks::getInstance()->apply_filters(
                'ws_assoc_persistence',
                new AssociationsPersistence
            );
        };

        $container['ws_listener_persistence'] = function ($c) {
            /**
             * Action: ws_listener_persistence
             * Description: Here you can choose a different websocket listener persistence implementation.
             * Expected return: \Conveyor\SocketHandlers\Interfaces\ListenerPersistenceInterface
             */
            return Hooks::getInstance()->apply_filters(
                'ws_listener_persistence',
                new ListenersPersistence
            );
        };

        $container['socket_communication'] = function ($c) {
            /**
             * Action: socket_communication
             * Description: Here you can choose a different websocket communication implementation.
             * Expected return: \Kanata\Interfaces\WebSocketCommunicationInterface
             */
            return Hooks::getInstance()->apply_filters(
                'socket_communication',
                new WebSocketCommunication(),
                container()
            );
        };

        $container['plugin_persistence'] = function ($c) {
            /**
             * Here we set the PluginRepository implementation.
             *
             * Interface: \Kanata\Repositories\Interfaces\Repositories
             */
            return new PluginRepository;
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

        /**
         * -----------------------------------------------------------
         * Proxy
         * -----------------------------------------------------------
         */

        $container['proxy'] = function ($c) {
            return  new Proxy();
        };
    }
}
