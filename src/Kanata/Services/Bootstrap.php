<?php

namespace Kanata\Services;

use Exception;
use Kanata\Commands\CreateCommand;
use Kanata\Commands\ListPluginCommand;
use Kanata\Commands\PublishPluginCommand;
use Kanata\Commands\ShellCommand;
use Kanata\Commands\StartHttpServerCommand;
use Kanata\Commands\StartMessageServiceCommand;
use Kanata\Commands\StartWsServerCommand;
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
    public static function startConsole(array $args = []): void
    {
        $application = new Application();

        try {
            self::processCore($args);
        } catch (Exception $e) {
            echo PHP_EOL . $e->getMessage() . PHP_EOL . PHP_EOL;
            return;
        }

        $application->add(new InfoCommand);
        $application->add(new StartHttpServerCommand);
        $application->add(new StartWsServerCommand);
        $application->add(new StartMessageServiceCommand);
        $application->add(new DebuggerCommand);
        $application->add(new ListPluginCommand);
        $application->add(new CreatePluginCommand);
        $application->add(new ActivatePluginCommand);
        $application->add(new DeactivatePluginCommand);
        $application->add(new ShellCommand);
        $application->add(new PublishPluginCommand);
        $application->add(new CreateCommand);

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
        self::processCore();
        Autoloader::startConstants();
        Autoloader::startHelpers();
    }

    public static function processCore(array $args = []): void
    {
        global $app, $container;

        Timer::set(['enable_coroutine' => true]);

        Autoloader::startEnv();
        Autoloader::startConstants();
        Autoloader::startHelpers();

        $container = new Container(['settings' => $_ENV]);
        $app = new App(new Psr17Factory, $container);

        Dependencies::start();
        Config::start();

        if (!isset($args['skip_plugins'])) {
            Autoloader::startPlugins();
        }
        Autoloader::startPluginHelpers();
    }
}
