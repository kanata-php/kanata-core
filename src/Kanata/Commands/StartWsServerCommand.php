<?php

namespace Kanata\Commands;

use Conveyor\SocketHandlers\Interfaces\SocketHandlerInterface;
use Conveyor\SocketHandlers\SocketMessageRouter;
use Error;
use Exception;
use Kanata\Events\WsClose;
use Kanata\Events\WsMessage;
use Kanata\Events\WsOpen;
use Psr\Container\ContainerInterface;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use voku\helper\Hooks;

class StartWsServerCommand extends Command
{
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
        handle_existing_pid(WS_PID_FILE);

        container()->set('context', 'ws');
        container()->set('input', $input);
        container()->set('output', $output);

        $persistence = socket_persistence();

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

        return Command::SUCCESS;
    }
}