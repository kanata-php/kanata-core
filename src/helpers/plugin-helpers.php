<?php

use Kanata\Repositories\PluginRepository;

if (! function_exists('get_plugins')) {
    /**
     * Get list of plugins.
     *
     * @return array
     */
    function get_plugins(): array {
        return PluginRepository::all()->toArray();
    }
}

if (! function_exists('get_active_plugins')) {
    /**
     * Get list of active plugins.
     *
     * @return array
     */
    function get_active_plugins(): array {
        return PluginRepository::get(['active' => true]);
    }
}

if (! function_exists('get_deactivated_plugins')) {
    /**
     * Get list of deactivated plugins.
     *
     * @return array
     */
    function get_deactivated_plugins(): array {
        return PluginRepository::get(['active' => false]);
    }
}

if (! function_exists('get_plugin')) {
    /**
     * Get list of deactivated plugins.
     *
     * @param string $name
     * @return array|null
     */
    function get_plugin(string $name): null|array {
        $plugin = PluginRepository::get(['name' => $name]);

        if (count($plugin) === 0) {
            return null;
        }

        return $plugin;
    }
}

if (! function_exists('deactivate_plugin')) {
    /**
     * Activate a plugin.
     *
     * @param string $name
     * @return bool
     */
    function deactivate_plugin(string $name): bool {
        $plugin = PluginRepository::get(['name' => $name]);

        if (empty($plugin)) {
            throw new Exception('Plugin not found');
        }

        return (new PluginRepository)->updatePlugin($plugin['id'], ['active' => false]);
    }
}

if (! function_exists('activate_plugin')) {
    /**
     * Deactivate a plugin.
     *
     * @param string $name
     * @return bool
     */
    function activate_plugin(string $name): bool {
        $plugin = PluginRepository::get(['name' => $name]);

        if (empty($plugin)) {
            throw new Exception('Plugin not found');
        }

        return (new PluginRepository)->updatePlugin($plugin['id'], ['active' => true]);
    }
}
