<?php

namespace Kanata\Services;

use Dotenv\Dotenv;

class Autoloader
{
    public static function startEnv(): void
    {
        $dotenv = Dotenv::createImmutable(ROOT_FOLDER);
        $dotenv->safeLoad();
    }

    public static function startConstants(): void
    {
        include_once __DIR__ . '/../../constants.php';
    }

    public static function startHelpers(): void
    {
        include_once __DIR__ . '/../../helpers.php';
    }

    public static function startPluginHelpers(): void
    {
        foreach(apply_filters('add_helpers', [[]]) as $helper) {
            include_once $helper;
        }
    }

    public static function startPlugins(): void
    {
        $connection = container()->db;
        $pluginLoader = new PluginLoader(container());
        $pluginLoader->load();
        unset($pluginLoader); // clear some memory.
        $connection->closeConnection();
    }
}
