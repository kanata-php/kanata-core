<?php

namespace Kanata\Services;

use Dotenv\Dotenv;
use Swoole\Exception;

class Autoloader
{
    public static function startEnv(): void
    {
        $dotenv = Dotenv::createImmutable(ROOT_FOLDER);
        $dotenv->safeLoad();
    }

    public static function startConstants(): void
    {
        if (!file_exists(ROOT_FOLDER . '/src/constants.php')){
            copy(ROOT_FOLDER . '/vendor/kanata-php/kanata-core/src/constants.php', __DIR__ . '/src/constants.php');
        }
        include_once ROOT_FOLDER . '/src/constants.php';
    }

    public static function startHelpers(): void
    {
        if (!file_exists(ROOT_FOLDER . '/src/helpers.php')){
            copy(ROOT_FOLDER . '/vendor/kanata-php/kanata-core/src/constants.php', __DIR__ . '/src/helpers.php');
        }
        include_once ROOT_FOLDER . '/src/helpers.php';
    }

    public static function startPlugins(): void
    {
        $pluginLoader = new PluginLoader(container());
        $pluginLoader->load();
        unset($pluginLoader); // clear some memory.
    }
}
