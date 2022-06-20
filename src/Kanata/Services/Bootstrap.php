<?php

namespace Kanata\Services;

use Exception;
use Illuminate\Database\Schema\Blueprint;
use Kanata\Commands\CreateCommand;
use Kanata\Commands\ListPluginCommand;
use Kanata\Commands\PublishPluginCommand;
use Kanata\Commands\ShellCommand;
use Kanata\Commands\StartHttpServerCommand;
use Kanata\Commands\StartMessageServiceCommand;
use Kanata\Commands\StartWsServerCommand;
use Kanata\Models\Plugin;
use Kanata\Models\WsAssociation;
use Kanata\Models\WsChannel;
use Kanata\Models\WsCommunication;
use Kanata\Models\WsListener;
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
        global $application;

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

        if (!isset($args['skip_run'])) {
            $application->run();
        }
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
            self::startPlugins();
        }
        Autoloader::startPluginHelpers();
    }

    public static function startPlugins()
    {
        Autoloader::startPlugins();
    }

    public static function migrateBase(bool $fresh_plugins, bool $fresh): void
    {
        self::migratePlugins($fresh_plugins);
        self::migrateWsChannels($fresh);
        self::migrateWsListeners($fresh);
        self::migrateWsCommunications($fresh);
        self::migrateWsAssociations($fresh);
    }

    public static function migratePlugins(bool $freshPlugins): void
    {
        if (
            $freshPlugins
            && mysql_table_exists(DB_DATABASE, Plugin::TABLE_NAME)
        ) {
            container()->db->schema()->drop(Plugin::TABLE_NAME);
        }

        if (!mysql_table_exists(DB_DATABASE, Plugin::TABLE_NAME)) {
            container()->db->schema()->create(Plugin::TABLE_NAME, function (Blueprint $table) {
                $table->increments('id');
                $table->boolean('active');
                $table->string('directory_name');
                $table->string('path');
                $table->string('name');
                $table->string('author_name')->nullable();
                $table->string('author_email')->nullable();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public static function migrateWsChannels(bool $fresh): void
    {
        if ($fresh && mysql_table_exists(DB_DATABASE, WsChannel::TABLE_NAME)) {
            container()->db->schema()->drop(WsChannel::TABLE_NAME);
        }
        if (!mysql_table_exists(DB_DATABASE, WsChannel::TABLE_NAME)) {
            container()->db->schema()->create(WsChannel::TABLE_NAME, function (Blueprint $table) {
                $table->increments('id');
                $table->integer('fd');
                $table->string('channel');
                $table->timestamps();
            });
        }
    }

    public static function migrateWsListeners(bool $fresh): void
    {
        if ($fresh && mysql_table_exists(DB_DATABASE, WsListener::TABLE_NAME)) {
        container()->db->schema()->drop(WsListener::TABLE_NAME);
        }
        if (!mysql_table_exists(DB_DATABASE, WsListener::TABLE_NAME)) {
            container()->db->schema()->create(WsListener::TABLE_NAME, function (Blueprint $table) {
                $table->increments('id');
                $table->integer('fd');
                $table->string('action');
                $table->timestamps();
            });
        }
    }

    public static function migrateWsCommunications(bool $fresh): void
    {
        if ($fresh && mysql_table_exists(DB_DATABASE, WsCommunication::TABLE_NAME)) {
            container()->db->schema()->drop(WsCommunication::TABLE_NAME);
        }
        if (!mysql_table_exists(DB_DATABASE, WsCommunication::TABLE_NAME)) {
            container()->db->schema()->create(WsCommunication::TABLE_NAME, function (Blueprint $table) {
                $table->increments('id');
                $table->string('action');
                $table->longText('data');
                $table->timestamps();
            });
        }
    }

    public static function migrateWsAssociations(bool $fresh): void
    {
        if ($fresh && mysql_table_exists(DB_DATABASE, WsAssociation::TABLE_NAME)) {
            container()->db->schema()->drop(WsAssociation::TABLE_NAME);
        }
        if (!mysql_table_exists(DB_DATABASE, WsAssociation::TABLE_NAME)) {
            container()->db->schema()->create(WsAssociation::TABLE_NAME, function (Blueprint $table) {
                $table->increments('id');
                $table->integer('fd');
                $table->integer('user_id');
                $table->timestamps();
            });
        }
    }
}
