<?php

if (! function_exists('make_path_relative_to_project')) {
    function make_path_relative_to_project(string $path): string
    {
        return str_replace(base_path(), '', $path);
    }
}

if (! function_exists('base_path')) {
    /**
     * Retrieve base path of the project.
     *
     * @param string $asset
     * @return string
     */
    function base_path(string $asset = ''): string
    {
        return trailingslashit(ROOT_FOLDER) . $asset;
    }
}

if (! function_exists('storage_path')) {
    /**
     * Retrieve storage path of the project.
     *
     * @return string
     */
    function storage_path(): string
    {
        return base_path() . 'storage/';
    }
}

if (! function_exists('public_path')) {
    /**
     * Retrieve public path of the project.
     *
     * @param string $asset
     * @return string
     */
    function public_path(string $asset = ''): string
    {
        return base_path() . 'public/' . $asset;
    }
}

if (! function_exists('resource_path')) {
    /**
     * Retrieve resources path of the project.
     *
     * @return string
     */
    function resource_path(): string
    {
        return base_path() . 'resources/';
    }
}

if (! function_exists('template_path')) {
    /**
     * Retrieve templates path of the project.
     *
     * @return string
     */
    function template_path(): string
    {
        return base_path() . 'resources/views';
    }
}

if (! function_exists('plugin_path')) {
    /**
     * Retrieve plugins path of the project.
     *
     * @param string|null $pluginDirectoryName
     * @return ?string
     */
    function plugin_path(?string $pluginDirectoryName = null): ?string
    {
        $partial_path = 'content/plugins';

        $path = base_path() . $partial_path;

        if (null === $pluginDirectoryName) {
            return $path;
        }

        $plugin_path = trailingslashit($path) . $pluginDirectoryName;

        if (!container()->filesystem->has($partial_path)) {
            return null;
        }

        return trailingslashit($plugin_path);
    }
}

if (! function_exists('content_path')) {
    /**
     * Retrieve content path of the project.
     *
     * @return string
     */
    function content_path(): string
    {
        return base_path() . 'content';
    }
}

if (! function_exists('trailingslashit')) {
    /**
     * Add trailing slash.
     *
     * (original from WordPress)
     *
     * Reference: https://developer.wordpress.org/reference/functions/trailingslashit/
     *
     * @param $string
     *
     * @return string
     */
    function trailingslashit($string): string
    {
        return untrailingslashit($string) . '/';
    }
}

if (! function_exists('untrailingslashit')) {
    /**
     * Remove trailing slash if it exists.
     *
     * (original from WordPress)
     *
     * Reference: https://developer.wordpress.org/reference/functions/untrailingslashit/
     *
     * @param $string
     *
     * @return string
     */
    function untrailingslashit($string): string
    {
        return rtrim($string, '/\\');
    }
}
