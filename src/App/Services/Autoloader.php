<?php

namespace Kanata\Services;

use Dotenv\Dotenv;

class Autoloader
{
    public static function startEnv(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();
    }

    public static function startConstants(): void
    {
        include_once __DIR__ . '/src/constants.php';
    }

    public static function startHelpers(): void
    {
        include_once __DIR__ . '/src/helpers.php';
    }

    public static function startPlugins(): void
    {
        $pluginLoader = new PluginLoader(container());
        $pluginLoader->load();
        unset($pluginLoader); // clear some memory.
    }
}
