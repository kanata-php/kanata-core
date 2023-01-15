<?php

namespace Kanata\Commands;

use Conveyor\SocketHandlers\Interfaces\SocketHandlerInterface;
use Conveyor\SocketHandlers\SocketMessageRouter;
use Error;
use Exception;
use Ilex\SwoolePsr7\SwooleResponseConverter;
use Ilex\SwoolePsr7\SwooleServerRequestConverter;
use Kanata\Commands\Traits\HttpRequest;
use Kanata\Events\WsClose;
use Kanata\Events\WsMessage;
use Kanata\Events\WsOpen;
use Kanata\Exceptions\UnauthorizedException;
use Kanata\Http\Middlewares\CoreMiddleware;
use Kanata\Services\Routes;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server as WebSocketServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use voku\helper\Hooks;

class StartWsServerCommand extends Command
{
    use HttpRequest;

    protected static $defaultName = 'ws';

    protected static $defaultDescription = 'Start WebSocket Server';

    protected function configure(): void
    {
        $this
            ->setHelp(self::$defaultDescription)
            ->addOption(WEBSOCKET_PORT_PARAM, null, InputOption::VALUE_OPTIONAL, 'WebSocket Custom Port. Default: ' . WS_SERVER_PORT, WS_SERVER_PORT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $app;

        handle_existing_pid(WS_PID_FILE);

        container()->set('context', 'ws');
        container()->set('input', $input);
        container()->set('output', $output);

        Routes::start();

        $psr17Factory = new Psr17Factory();

        $requestConverter = new SwooleServerRequestConverter($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        $channelPersistence = ws_channel_persistence();
        $userAssocPersistence = ws_assoc_persistence();
        $listenerPersistence = ws_listener_persistence();
        $persistence = [$channelPersistence, $userAssocPersistence, $listenerPersistence];

        // refresh persistence
        new SocketMessageRouter(
            persistence: $persistence,
            fresh: true,
        );

        $communications = socket_communication();

        $port = $input->getOption(WEBSOCKET_PORT_PARAM);

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

        $server_settings = [
            'document_root' => public_path(),
            'enable_static_handler' => true,
        ];

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

        $websocket->on("start", function (WebSocketServer $server) use ($port, $communications, $channelPersistence) {
            file_put_contents(WS_PID_FILE, $server->master_pid);

            echo 'Swoole Server is started at ws://' . $server->host . ':' . $port;

            if (!(bool) WS_TICK_ENABLED) {
                return;
            }

            $server->tick((int) WS_TICK_INTERVAL, function () use ($server, $communications, $channelPersistence) {
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
                        foreach ($channelPersistence->getAllConnections() as $key => $item) {
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
                            $channelPersistence->disconnect($fd);
                        }
                    }
                }
            });
        });

        $websocket->on('open', function (WebSocketServer $server, Request $request) {
            echo "WS Opened: " . $request->fd . PHP_EOL;

            dispatch_event(new WsOpen($request->fd));

            /**
             * Action: socket_start_checkpoint
             * Description: Checkpoint for websocket connection opened.
             * Expected return: void
             * @param WebSocketServer $server
             * @param Request $request
             */
            do_action('socket_start_checkpoint', [$server, $request]);
        });

        $websocket->on('message', function (WebSocketServer $server, Frame $frame) use ($persistence) {
            echo 'Received message (' . $frame->fd . '): ' . $frame->data . PHP_EOL;

            dispatch_event(new WsMessage($frame->fd));

            /**
             * Action: socket_actions
             * Description: Important for Socket Actions specifications via plugins.
             * Expected return: SocketHandlerInterface
             * @param SocketHandlerInterface $socketRouter
             * @param ContainerInterface $container
             */
            $socketRouter = apply_filters(
                'socket_actions',
                [
                    new SocketMessageRouter($persistence),
                    container(),
                ]
            );

            try {
                $socketRouter($frame->data, $frame->fd, $server);
            } catch (Exception $e) {
                logger()->error('There was a problem while handling WS Message: ' . $e->getMessage());
            }
        });

        $websocket->on('Disconnect', function(WebSocketServer $server, int $fd) {
            echo 'Connection disconnected: ' . $fd . PHP_EOL;
        });

        $websocket->on('close', function ($server, $fd) {
            echo "WS Close: " . $fd . PHP_EOL;

            dispatch_event(new WsClose($fd));

            /**
             * Action: ws_close
             * Description: Execute an action during connection close event.
             *
             * @param int $fd
             */
            Hooks::getInstance()->do_action('ws_close', $fd);
        });

        $websocket->on("request", new CoreMiddleware(function (
            Request $swooleRequest, Response $swooleResponse
        ) use ($app, $requestConverter, $websocket) {
            $psr7Request = $requestConverter->createFromSwoole($swooleRequest);

            try {
                $psr7Request = $this->addMiddlewareHttpRequest($psr7Request);
                $psr7Response = $app->handle($psr7Request);
            } catch (UnauthorizedException $e) {
                $swooleResponse->status(403, 'Unauthorized procedure!');
                return $swooleResponse->end($this->getUnauthorizedView());
            }

            $converter = new SwooleResponseConverter($swooleResponse);
            $converter->send($this->processResponse($psr7Request, $psr7Response));
        }));

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

        $app->getContainer()->set('server', $websocket);

        $websocket->start();

        return Command::SUCCESS;
    }
}
