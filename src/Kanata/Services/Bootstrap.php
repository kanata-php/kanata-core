<?php

namespace Kanata\Services;

use Exception;
use Kanata\Commands\CreateCommand;
use Kanata\Commands\PublishPluginCommand;
use Kanata\Commands\ShellCommand;
use Slim\App;
use Nyholm\Psr7\Factory\Psr17Factory;
use Kanata\Commands\ActivatePluginCommand;
use Kanata\Commands\DeactivatePluginCommand;
use Kanata\Commands\DebuggerCommand;
use Kanata\Commands\InfoCommand;
use Kanata\Commands\CreatePluginCommand;
use Swoole\Timer;
use Symfony\Component\Console\Application;
use voku\helper\Hooks;

class Bootstrap
{
    public static function startServers(): void
    {
        global $argv;

        $coreArgs = [];

        switch (true) {
            case in_array('--websocket', $argv):
                $server_type = Servers::WEBSOCKET;
                break;
            case in_array('--queue', $argv):
                $server_type = Servers::QUEUE;
                break;
            default:
                // $coreArgs = ['start_session' => true];
                $server_type = Servers::HTTP;
        }

        self::processCore($coreArgs);
        Routes::start();
        Servers::start($server_type);
    }

    public static function startConsole(): void
    {
        $application = new Application();
        try {
            self::processCore([
                'skip_console' => true,
            ]);
        } catch (Exception $e) {
            echo PHP_EOL . $e->getMessage() . PHP_EOL . PHP_EOL;
            return;
        }

        $application->add(new InfoCommand());
        $application->add(new DebuggerCommand());
        $application->add(new CreatePluginCommand());
        $application->add(new ActivatePluginCommand());
        $application->add(new DeactivatePluginCommand());
        $application->add(new ShellCommand());
        $application->add(new PublishPluginCommand());
        $application->add(new CreateCommand());

        /**
         * Action: commands
         * Description: Important for extra commands registration via plugins.
         * Expected return: Application
         * @param Application $application
         */
        $application = Hooks::getInstance()->apply_filters('commands', $application);

        $application->run();
    }

    public static function bootstrapTinkerwell(): void
    {
        self::processCore();
        Autoloader::startConstants();
        Autoloader::startHelpers();
    }

    public static function bootstrapPhpunit(): void
    {
        self::processCore(['skip_console' => true]);
        Autoloader::startConstants();
        Autoloader::startHelpers();
    }

    public static function processCore(array $args = []): void
    {
        global $app, $container;

        Timer::set(['enable_coroutine' => true]);

        if (isset($args['only_plugins'])) {
            Autoloader::startPlugins();
            return;
        }

        Autoloader::startEnv();
        Autoloader::startConstants();
        Autoloader::startHelpers();

        $container = new Container(['settings' => $_ENV]);
        $app = new App(new Psr17Factory, $container);

        if (!isset($args['skip_console'])) {
            Console::start();
        }

        Dependencies::start();
        Config::start();

        if (!isset($args['skip_plugins'])) {
            Autoloader::startPlugins();
        }

        Autoloader::startPluginHelpers();
    }
}
