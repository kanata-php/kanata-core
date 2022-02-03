<?php

namespace Kanata\Services;

use Exception;
use Slim\App;
use Nyholm\Psr7\Factory\Psr17Factory;
use Kanata\Commands\ActivatePluginCommand;
use Kanata\Commands\DebuggerCommand;
use Kanata\Commands\InfoCommand;
use Kanata\Commands\CreatePluginCommand;
use Kanata\Commands\InitCommand;
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
        Autoloader::startConstants();
        Autoloader::startHelpers();

        $application = new Application();

        try {
            self::processCore();
        } catch (Exception $e) {
            echo PHP_EOL . $e->getMessage() . PHP_EOL . PHP_EOL;
            return;
        }

        $application->add(new InitCommand());
        $application->add(new InfoCommand());
        $application->add(new DebuggerCommand());
        $application->add(new CreatePluginCommand());
        $application->add(new ActivatePluginCommand());

        /**
         * Action: commands
         * Description: Important for extra commands registration via plugins.
         * Expected return: Application
         * @param Application $application
         */
        $application = Hooks::getInstance()->apply_filters('commands', $application);

        $application->run();
    }

    private static function processCore(): void
    {
        global $app, $container;

        Autoloader::startEnv();
        Autoloader::startConstants();
        Autoloader::startHelpers();

        $app = new App(new Psr17Factory, new Container(['settings' => $_ENV]));
        $container = $app->getContainer();

        Console::start();
        Dependencies::start();
        Config::start();
        Autoloader::startPlugins();
    }
}