<?php

namespace Kanata\Services;

use Error;
use Slim\App;
use Exception;
use voku\helper\Hooks;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Kanata\Http\Middlewares\CoreMiddleware;
use Kanata\Http\Middlewares\FormMiddleware;
use Ilex\SwoolePsr7\SwooleResponseConverter;
use Kanata\Exceptions\UnauthorizedException;
use Psr\Http\Message\ServerRequestInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Swoole\WebSocket\Server as WebSocketServer;
use Conveyor\SocketHandlers\SocketMessageRouter;
use Ilex\SwoolePsr7\SwooleServerRequestConverter;
use Conveyor\SocketHandlers\Interfaces\SocketHandlerInterface;
use Kanata\Http\Middlewares\SessionMiddleware;

class Servers
{
    const HTTP = 'http';
    const WEBSOCKET = 'websocket';
    const QUEUE = 'queue';

    public static function start(string $server_type = self::HTTP): void
    {
        global $app;

        $psr17Factory = new Psr17Factory();

        $requestConverter = new SwooleServerRequestConverter($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        switch ($server_type) {
            case $server_type === self::WEBSOCKET:
                self::startWebsocketServer();
                break;
            case $server_type === self::QUEUE:
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

        /**
         * Action: websocket_mode
         * Description: Important for WebSocket server mode.
         * Expected return: string
         * @param string $server_mode
         */
        $server_mode = Hooks::getInstance()->apply_filters(
            'websocket_mode',
            SWOOLE_BASE
        );

        $server_settings = [];

        if (WS_SERVER_SSL === true) {
            $websocket = new WebSocketServer(WS_SERVER_HOST, $port, $server_mode, SWOOLE_SOCK_TCP | SWOOLE_SSL);
            $server_settings = [
                'ssl_cert_file' => WS_SSL_CERTIFICATE,
                'ssl_key_file' => WS_SSL_KEY,
            ];
        } else {
            $websocket = new WebSocketServer(WS_SERVER_HOST, $port, $server_mode, SWOOLE_SOCK_TCP);
        }

        /**
         * Action: websocket_settings
         * Description: Important for Http custom settings.
         * Expected return: array
         * @param array $server_settings
         */
        $server_settings = Hooks::getInstance()->apply_filters(
            'websocket_settings',
            $server_settings
        );

        $websocket->set($server_settings);

        $websocket->on("start", function (WebSocketServer $server) use ($port, $communications, $persistence) {
            file_put_contents(WS_PID_FILE, $server->master_pid);

            echo 'Swoole Server is started at ws://' . $server->host . ':' . $port;

            if (!(bool) WS_TICK_ENABLED) {
                return;
            }

            $server->tick((int) WS_TICK_INTERVAL, function () use ($server, $communications, $persistence) {
                $dataList = $communications->get(WS_MESSAGE_ACTION);
                if (empty($dataList)) {
                    return;
                }

                $communications->clean(WS_MESSAGE_ACTION);
                foreach ($dataList as $data) {
                    $communications->delete($data['id']);
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
                        $connections = [];
                        foreach ($server->connections as $item) {
                            $connections[] = $item;
                        }
                    }

                    /**
                     * Action: tick_connections_broadcast
                     * Description: Filter connections.
                     * Expected return: array $content
                     * @param SocketHandlerInterface $socketRouter
                     * @param array $content
                     */
                    try {
                        $connections = Hooks::getInstance()->apply_filters(
                            'tick_connections_broadcast',
                            $connections,
                            $content
                        );
                    } catch (Exception|Error $e) {
                        logger()->error('There was an error while trying to filter ticker broadcast. Error: ' . $e->getMessage());
                    }

                    foreach ($connections as $fd) {
                        if (!$server->push($fd, json_encode($data))) {
                            $persistence->disconnect($fd);
                        }
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

            /**
             * Action: ws_close
             * Description: Execute an action during connection close event.
             *
             * @param int $fd
             */
            Hooks::getInstance()->do_action('ws_close', $fd);

            echo "WS Close: " . $fd . PHP_EOL;
        });

        /**
         * Action: websocket_server
         * Description: Important for WebSocket custom or overwritten callbacks.
         * Expected return: WebSocketServer
         * @param WebSocketServer $websocket
         */
        $websocket = Hooks::getInstance()->apply_filters(
            'websocket_server',
            $websocket
        );

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

        /**
         * Action: http_mode
         * Description: Important for Http server mode.
         * Expected return: string
         * @param string $server_mode
         */
        $server_mode = Hooks::getInstance()->apply_filters(
            'http_mode',
            SWOOLE_BASE
        );

        $server_settings = [
            'document_root' => public_path(),
            'enable_static_handler' => true,
        ];

        if (HTTP_SERVER_SSL === true) {
            $server = new Server(
                HTTP_SERVER_HOST,
                get_input()->getOption(HTTP_PORT_PARAM),
                $server_mode,
                SWOOLE_SOCK_TCP | SWOOLE_SSL
            );

            $server_settings = array_merge($server_settings, [
                'ssl_cert_file' => SSL_CERTIFICATE,
                'ssl_key_file' => SSL_KEY,
            ]);
        } else {
            $server = new Server(
                HTTP_SERVER_HOST,
                get_input()->getOption(HTTP_PORT_PARAM),
                $server_mode,
                SWOOLE_SOCK_TCP
            );
        }

        /**
         * Action: http_settings
         * Description: Important for Http custom settings.
         * Expected return: array
         * @param array $server_settings
         */
        $server_settings = Hooks::getInstance()->apply_filters('http_settings', $server_settings);

        $server->set($server_settings);

        $server->on("start", function (Server $server) {
            echo 'Swoole Server is started at http://' . $server->host . ':' . $server->port . PHP_EOL;
        });

        $server->on("request", new CoreMiddleware(function (
            Request $swooleRequest, Response $swooleResponse
        ) use ($app, $requestConverter) {
            $psr7Request = $requestConverter->createFromSwoole($swooleRequest);

            try {
                $psr7Request = self::addMiddlewareHttpRequest($psr7Request);
                $psr7Response = $app->handle($psr7Request);
            } catch (UnauthorizedException $e) {
                $swooleResponse->status(403, 'Unauthorized procedure!');
                return $swooleResponse->end(self::getUnauthorizedView());
            }

            $converter = new SwooleResponseConverter($swooleResponse);
            $converter->send(self::processResponse($psr7Request, $psr7Response));
        }));

        /**
         * Action: http_server
         * Description: Important for Http custom or overwritten callbacks.
         * Expected return: Server
         * @param Server $server
         */
        $server = Hooks::getInstance()->apply_filters('http_server', $server);

        $server->start();
    }

    private static function getUnauthorizedView(): string
    {
        /**
         * Action: unauthorized_view
         * Description: Customize unauthorized view.
         * @param string
         */
        $unauthorized_view = Hooks::getInstance()->apply_filters('unauthorized_view', 'core::exceptions/unauthorized');
        return container()->view->render($unauthorized_view);
    }

    private static function addMiddlewareHttpRequest(ServerRequestInterface $psr7Request): ServerRequestInterface
    {
        /**
         * Action: http_middleware
         * Description: Allows HTTP middleware execution on Psr7 Request.
         * @param ServerRequestInterface $request
         */
        $psr7Request = Hooks::getInstance()->apply_filters('http_middleware', $psr7Request);

        /**
         * Action: http_session_middleware
         * Description: Enable/Disable Session Middleware to HTTP request.
         * @param bool $active
         */
        if (Hooks::getInstance()->apply_filters('http_session_middleware', true)) {
            $psr7Request = (new SessionMiddleware)($psr7Request);
        }

        /**
         * Action: http_form_middleware
         * Description: Enable/Disable Form Middleware to HTTP request, useful for cached input.
         * @param bool $active
         */
        if (Hooks::getInstance()->apply_filters('http_form_middleware', true)) {
            $psr7Request = (new FormMiddleware)($psr7Request);
        }

        return $psr7Request;
    }

    private static function processResponse(ServerRequestInterface $psr7Request, ResponseInterface $psr7Response)
    {
        Session::addCookiesToResponse($psr7Request, $psr7Response);

        /**
         * Action: intercept_http_response
         * Description: Intercept Psr7 HTTP response.
         * @param ResponseInterface
         */
        $psr7Response = Hooks::getInstance()->apply_filters('intercept_http_response', $psr7Response);

        return $psr7Response;
    }
}
