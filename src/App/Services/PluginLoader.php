<?php

namespace Kanata\Services;

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
            $this->loadPlugin($plugin);
            $pluginsFound[] = $plugin['id'];
        }

        $this->unregisterIfNotFound($pluginsFound);
    }

    private function unregisterIfNotFound(array $pluginsFound): void
    {
        $registeredPlugins = [];
        foreach (PluginRepository::all() as $registered) {
            $registeredPlugins[] = $registered['id'];
        }

        $remaining = array_filter($registeredPlugins, function ($item) use ($pluginsFound) {
            return !in_array($item, $pluginsFound);
        });

        foreach($remaining as $item) {
            PluginRepository::delete($item);
        }
    }

    public function loadPlugin(array $plugin)
    {
        $this->loadPluginClass($plugin);

        $className = $this->pluginRepository->getClassName($plugin);
        $reflectionClass = new ReflectionClass($className);
        $this->loadPluginAnnotations($plugin, $reflectionClass);

        if ($plugin['active']) {
            $this->loader->addPrefix($className, $plugin['path'] . '/src');
            $instance = $reflectionClass->newInstanceArgs([container()]);
            $instance->start();
        }
    }

    private function loadPluginClass(array $plugin): void
    {
        $mainFile = $this->pluginRepository->getMainFile($plugin);
        $className = $this->pluginRepository->getClassName($plugin);

        $this->loader->setClassFile($className, $mainFile);
    }

    private function loadPluginAnnotations(array &$plugin, ReflectionClass $reflectionClass): void
    {
        $reader = new AnnotationReader();
        $realName = $reader->getClassAnnotation($reflectionClass, PluginAnnotation::class);
        $realAuthor = $reader->getClassAnnotation($reflectionClass, AuthorAnnotation::class);
        $realDescription = $reader->getClassAnnotation($reflectionClass, DescriptionAnnotation::class);

        if (
            $plugin['name'] !== $realName->name
            || $plugin['author_name'] !== $realAuthor->name
            || $plugin['author_email'] !== $realAuthor->email
            || $plugin['description'] !== $realDescription->value
        ) {
            $data = [
                'name' => $realName->name,
                'author_name' => $realAuthor->name,
                'author_email' => $realAuthor->email,
                'description' => $realDescription->value,
            ];
            $result = $this->pluginRepository->updatePlugin($plugin['id'], $data);
            if (!$result) {
                logger()->info('There was an error while updating a plugin info: ' . implode(', ', $this->pluginRepository->errors));
            }
        }
    }
}
