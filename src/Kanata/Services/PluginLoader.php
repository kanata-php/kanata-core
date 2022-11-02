<?php

namespace Kanata\Services;

use Kanata\Models\Plugin;
use Kanata\Repositories\PluginRepository;
use Doctrine\Common\Annotations\AnnotationReader;
use FilesystemIterator;
use IteratorIterator;
use Psr\Container\ContainerInterface;
use RecursiveDirectoryIterator;
use ReflectionClass;
use Aura\Autoload\Loader;
use Kanata\Annotations\Plugin as PluginAnnotation;
use Kanata\Annotations\Author as AuthorAnnotation;
use Kanata\Annotations\Description as DescriptionAnnotation;

/**
 * Class PluginLoader
 *
 * Class that loads plugins into the application, find them in the declared classes array
 * and start them.
 *
 * @package Kanata\Services
 */

class PluginLoader
{
    protected PluginRepository $pluginRepository;
    protected Loader $loader;

    public function __construct(
        protected ContainerInterface $container
    ) {
        $this->pluginRepository = new PluginRepository();

        $this->loader = new Loader;
        $this->loader->register();
    }

    /**
     * Iterate through all plugins.
     * @return void
     */
    public function load(): void
    {
        if (defined('SKIP_PLUGINS_FOLDER')) {
            return;
        }

        $main_directory = base_path() . 'content/plugins';
        $directory = new RecursiveDirectoryIterator($main_directory, FilesystemIterator::SKIP_DOTS);
        $iterator = new IteratorIterator($directory);
        $pluginsFound = [];

        foreach ($iterator as $info) {
            if (filetype($info->getPathname()) !== 'dir') {
                continue;
            }

            $pluginPath = $info->getPathname();
            $plugin = $this->pluginRepository->registerIfNotRegistered($pluginPath);

            // this might happen when change environment
            if (null !== $plugin && !file_exists($plugin->path) && file_exists($pluginPath)) {
                $this->unregisterIfNotFound([$pluginsFound]);
                $plugin = $this->pluginRepository->registerIfNotRegistered($pluginPath);
            }

            if (!$this->loadPlugin($plugin)) {
                continue;
            }
            $pluginsFound[] = $plugin?->id;
        }

        $this->unregisterIfNotFound($pluginsFound);
    }

    private function unregisterIfNotFound(array $pluginsFound): void
    {
        $registeredPlugins = PluginRepository::all()->pluck('id');

        $registeredPlugins->filter(function($item) use ($pluginsFound) {
            return !in_array($item, $pluginsFound);
        })->each(function($item) {
            PluginRepository::delete($item);
        });
    }

    public function loadPlugin(Plugin $plugin): bool
    {
        $this->loadPluginClass($plugin);

        $className = $this->pluginRepository->getClassName($plugin);
        if (!class_exists($className)) {
            return false;
        }

        $reflectionClass = new ReflectionClass($className);
        $this->loadPluginAnnotations($plugin, $reflectionClass);

        if ($plugin->active) {
            $this->loader->addPrefix($className, $plugin->path . '/src');
            $instance = $reflectionClass->newInstanceArgs([container()]);
            $instance->start();
        }

        return true;
    }

    private function loadPluginClass(Plugin $plugin): void
    {
        $mainFile = $this->pluginRepository->getMainFile($plugin);
        $className = $this->pluginRepository->getClassName($plugin);

        $this->loader->setClassFile($className, $mainFile);
    }

    private function loadPluginAnnotations(Plugin &$plugin, ReflectionClass $reflectionClass): void
    {
        $reader = new AnnotationReader();
        $realName = $reader->getClassAnnotation($reflectionClass, PluginAnnotation::class);
        $realAuthor = $reader->getClassAnnotation($reflectionClass, AuthorAnnotation::class);
        $realDescription = $reader->getClassAnnotation($reflectionClass, DescriptionAnnotation::class);

        if (
            $plugin->name !== $realName->name
            || (
                !empty($realAuthor->name)
                && !empty($plugin->author_name)
                && $plugin->author_name !== $realAuthor->name
            )
            || (
                !empty($realAuthor->email)
                && !empty($plugin->author_email)
                && $plugin->author_email !== $realAuthor->email
            )
            || (
                !empty($realDescription->value)
                && !empty($plugin->description)
                && $plugin->description !== $realDescription->value
            )
        ) {
            $data = [
                'name' => $realName->name,
                'author_name' => $realAuthor->name,
                'author_email' => $realAuthor->email,
                'description' => $realDescription->value,
            ];
            $result = $this->pluginRepository->updatePlugin($plugin->id, $data);
            if (!$result) {
                logger()->error('There was an error while updating a plugin info: ' . implode(', ', $this->pluginRepository->errors));
                logger()->debug('Error data: ' . json_encode([
                    'plugin_id' => $plugin->id,
                    'data' => $data,
                    'update_pre_condition' => $plugin->name !== $realName->name
                        || $plugin->author_name !== $realAuthor->name
                        || $plugin->author_email !== $realAuthor->email
                        || $plugin->description !== $realDescription->value,
                    'update_each_pre_condition' => json_encode([
                        'name' => $plugin->name !== $realName->name,
                        'author_name' => $plugin->author_name !== $realAuthor->name,
                        'author_email' => $plugin->author_email !== $realAuthor->email,
                        'description' => $plugin->description !== $realDescription->value,
                    ]),
                ]));
            }
        }
    }
}
