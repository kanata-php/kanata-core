<?php

namespace Kanata\Commands;

use Ilex\SwoolePsr7\SwooleResponseConverter;
use Ilex\SwoolePsr7\SwooleServerRequestConverter;
use Kanata\Commands\Traits\HttpRequest;
use Kanata\Exceptions\UnauthorizedException;
use Kanata\Http\Middlewares\CoreMiddleware;
use Kanata\Services\Routes;
use Nyholm\Psr7\Factory\Psr17Factory;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use voku\helper\Hooks;

class StartHttpServerCommand extends Command
{
    use HttpRequest;

    protected static $defaultName = 'http';

    protected static $defaultDescription = 'Start HTTP Server';

    protected function configure(): void
    {
        $this
            ->setHelp(self::$defaultDescription)
            ->addOption(HTTP_PORT_PARAM, null, InputOption::VALUE_OPTIONAL, 'HTTP Custom Port. Default: ' . HTTP_PORT_PARAM, HTTP_SERVER_PORT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $app;

        container()->set('context', 'http');
        container()->set('input', $input);
        container()->set('output', $output);

        Routes::start();

        $psr17Factory = new Psr17Factory();

        $requestConverter = new SwooleServerRequestConverter($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        handle_existing_pid(PID_FILE);

        /**
         * Action: http_mode
         * Description: Important for Http server mode.
         * Expected return: string
         * @param string $server_mode
         */
        $server_mode = Hooks::getInstance()->apply_filters(
            'http_mode',
            SWOOLE_PROCESS
        );

        $server_settings = [
            'document_root' => public_path(),
            'enable_static_handler' => true,
        ];

        if (HTTP_SERVER_SSL === true) {
            $server = new Server(
                HTTP_SERVER_HOST,
                $input->getOption(HTTP_PORT_PARAM),
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
                $input->getOption(HTTP_PORT_PARAM),
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
        ) use ($app, $requestConverter, $server) {
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
         * Action: http_server
         * Description: Important for Http custom or overwritten callbacks.
         * Expected return: Server
         * @param Server $server
         */
        $server = Hooks::getInstance()->apply_filters('http_server', $server);

        $app->getContainer()->set('server', $server);

        $server->start();

        return Command::SUCCESS;
    }
}