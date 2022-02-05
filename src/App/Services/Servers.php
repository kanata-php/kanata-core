<?php

namespace Kanata\Services;

use Ilex\SwoolePsr7\SwooleServerRequestConverter;
use Ilex\SwoolePsr7\SwooleResponseConverter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Slim\App;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use voku\helper\Hooks;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Conveyor\SocketHandlers\Interfaces\SocketHandlerInterface;
use Conveyor\SocketHandlers\SocketMessageRouter;
use Psr\Container\ContainerInterface;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;

class Servers
{

    public static function start(): void
    {
        global $app, $argv;

        $psr17Factory = new Psr17Factory();

        $requestConverter = new SwooleServerRequestConverter($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        switch (true) {
            case in_array('--websocket', $argv):
                self::startWebsocketServer();
                break;
            case in_array('--queue', $argv):
                self::startMessageService();
                break;
            default:
                self::startHttpServer($app, $requestConverter);
        }
    }

    private static function startWebsocketServer(): void
    {
        handle_existing_pid(WS_PID_FILE);

        $persistence = socket_persistence();

        $communications = socket_communication();

        $port = get_input()->getOption(WEBSOCKET_PORT_PARAM);

        $websocket = new WebSocketServer(WS_SERVER_HOST, $port);

        $websocket->on("start", function (WebSocketServer $server) use ($port, $communications, $persistence) {
            file_put_contents(WS_PID_FILE, $server->master_pid);

            echo 'Swoole Server is started at ws://' . $server->host . ':' . $port;

            if (!(bool) WS_TICK_ENABLED) {
                return;
            }

            $server->tick((int) WS_TICK_INTERVAL, function () use ($server, $communications, $persistence) {
                $data = $communications->get(WS_MESSAGE_ACTION);
                if (count($data) === 0) {
                    return;
                }

                $data = current($data);
                $communications->clean(WS_MESSAGE_ACTION);
                $content = json_decode($data['data'], true);
                if (isset($content['channel'])) {
                    $connections = [];
                    foreach ($persistence->getAllConnections() as $key => $item) {
                        if ($item !== $content['channel']) {
                            continue;
                        }
                        $connections[] = $key;
                    }
                } else {
                    $connections = $server->connections;
                }

                foreach ($connections as $fd) {
                    if (!$server->push($fd, json_encode($data))) {
                        $persistence->disconnect($fd);
                    }
                }
            });
        });

        $websocket->on('open', function (WebSocketServer $server, Request $request) {
            echo "WS Opened: " . $request->fd . PHP_EOL;
        });

        $websocket->on('message', function (WebSocketServer $server, Frame $frame) use ($persistence) {
            echo 'Received message (' . $frame->fd . '): ' . $frame->data . PHP_EOL;

            /**
             * Action: socket_actions
             * Description: Important for Socket Actions specifications via plugins.
             * Expected return: SocketHandlerInterface
             * @param SocketHandlerInterface $socketRouter
             * @param ContainerInterface     $container
             */
            $socketRouter = Hooks::getInstance()->apply_filters(
                'socket_actions',
                new SocketMessageRouter($persistence),
                container()
            );

            $socketRouter($frame->data, $frame->fd, $server);
        });


        $websocket->on('Disconnect', function(WebSocketServer $server, int $fd)
        {
            echo 'Connection disconnected: ' . $fd . PHP_EOL;
        });

        $websocket->on('close', function ($server, $fd) {
            echo "WS Close: " . $fd . PHP_EOL;
        });

        $websocket->start();
    }

    private static function startMessageService(): void
    {
        $connection = new AMQPStreamConnection(
            QUEUE_SERVER_HOST,
            QUEUE_SERVER_PORT,
            QUEUE_SERVER_USER,
            QUEUE_SERVER_PASSWORD
        );
        $channel = $connection->channel();

        $default_queue_attributes = [
            'exchange' => 'default',
            'exchange_type' => 'topic',
            'queue' => 'default',
            'routing_key' => '',
            'option' => '--default',
            'callback' => null,
        ];

        /**
         * This is the default queue.
         */
        $queues = [];
        if (get_input()->getOption(QUEUE_NAME_CONSOLE_OPTION) === 'default') {
            $queues = !DEFAULT_QUEUE ? [] : [
                'default' => [
                    'callback' => function ($msg) {
                        container()->logger->info("Default handler received: " . $msg->body . PHP_EOL);
                    },
                ],
            ];
        }

        /**
         * Important: don't forget to register the option as a new supervisor service for each extra queue added through
         * this hook. The `option` parameter here is the option passed to the command there.
         *
         * Command on supervisor: /usr/bin/php /var/www/html/index.php --queue --queue-name=example-option
         *
         * Format:
         *     [
         *         [
         *             'queue' => 'queue-name', 
         *             'exchange' => 'example-exchange',
         *             'routing-key' => 'example-routing-key',
         *             'option' => 'example-option',
         *             'callback' => Callable,
         *         ],
         *         ...
         *     ]
         */
        $queues = Hooks::getInstance()->apply_filters('queues', $queues);

        foreach ($queues as $queue) {
            $queue = array_merge($default_queue_attributes, $queue);

            if (get_input()->getOption(QUEUE_NAME_CONSOLE_OPTION) !== $queue['option']) {
                continue;
            }

            try {
                $channel->exchange_declare($queue['exchange'], $queue['exchange_type'], false, false, false);
                $channel->queue_declare($queue['queue'], false, false, false, false);
                $channel->queue_bind($queue['queue'], $queue['exchange'], $queue['routing_key']);
                $channel->basic_consume($queue['queue'], '', false, true, false, false, $queue['callback']);
            } catch (Exception $e) {
                logger()->error(
                    'There was an error while starting queues: ' . $e->getMessage() . PHP_EOL
                    . 'Data: ' . json_encode($queue)
                );
                continue;
            }

            logger()->info('Queue Service: [' . $queue['queue'] . '] Waiting for messages.' . PHP_EOL);
        }

        while ($channel->is_open()) {
            $channel->wait();
        }
    }

    private static function startHttpServer(App $app, SwooleServerRequestConverter $requestConverter): void
    {
        handle_existing_pid(PID_FILE);

        $server = new Server(HTTP_SERVER_HOST, get_input()->getOption(HTTP_PORT_PARAM));

        $server->set([
            'document_root' => public_path(),
            'enable_static_handler' => true,
        ]);

        $server->on("start", function (Server $server) {
            global $argv;

            file_put_contents(PID_FILE, $server->master_pid);

            echo 'Swoole Server is started at http://' . $server->host . ':' . $server->port . PHP_EOL;
        });

        $server->on("request", function (
            Request $swooleRequest, Response $swooleResponse
        ) use ($app, $requestConverter) {
            $psr7Request = $requestConverter->createFromSwoole($swooleRequest);
            $psr7Response = $app->handle($psr7Request);
            $converter = new SwooleResponseConverter($swooleResponse);
            $converter->send($psr7Response);
        });

        $server->start();
    }
}