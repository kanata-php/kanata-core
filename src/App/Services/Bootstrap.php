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
use Symfony\Component\Console\Application;
use voku\helper\Hooks;

class Bootstrap
{
    public static function startServers(): void
    {
        self::processCore();
        Routes::start();
        Servers::start();
    }

    public static function startConsole(): void
    {
        $application = new Application();
        try {
            self::processCore(['skip_console' => true]);
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

    public static function processCore(array $args = []): void
    {
        global $app, $container;

        Autoloader::startEnv();
        Autoloader::startConstants();
        Autoloader::startHelpers();

        $app = new App(new Psr17Factory, new Container(['settings' => $_ENV]));
        $container = $app->getContainer();

        if (!isset($args['skip_console'])) {
            Console::start();
        }

        Dependencies::start();
        Config::start();

        if (!isset($args['skip_plugins'])) {
            Autoloader::startPlugins();
        }
    }
}
