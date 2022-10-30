<?php

namespace Kanata\Commands;

use Carbon\Carbon;
use Kanata\Commands\Traits\LogoTrait;
use KanataPlugin\KanataPlugin;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SearchPluginCommand extends Command
{
    use LogoTrait;

    const PLUGINS_DATA_KEY = 'plugins-data-key';

    protected static $defaultName = 'plugin:search';

    protected static $defaultDescription = 'This command search for Kanata plugins';

    protected function configure(): void
    {
        $this
            ->setHelp(self::$defaultDescription)
            ->setDefinition(
                new InputDefinition([
                    new InputOption(
                        name: 'search',
                        shortcut: null,
                        mode: InputOption::VALUE_OPTIONAL,
                        description: 'The name to search for.',
                        default: null,
                    ),
                    new InputOption(
                        name: 'plugin',
                        shortcut: null,
                        mode: InputOption::VALUE_OPTIONAL,
                        description: 'The name of the plugin to get info about.',
                        default: null,
                    ),
                    new InputOption(
                        name: 'no-cache',
                        shortcut: null,
                        mode: InputOption::VALUE_NONE,
                        description: 'Get plugins without cache.',
                        // default: false,
                    ),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $search = $input->getOption('search');
        $plugin = $input->getOption('plugin');

        if (null !== $search && null !== $plugin) {
            $io->error('Search and Plugin options must not be used together!');
            return Command::FAILURE;
        }

        $noCache = $input->getOption('no-cache');

        $io->title('Plugins API');

        $io->info('Requesting plugins...');
        $results = $this->getPluginsData($search, $plugin, $noCache);

        if (null === $results) {
            $io->info('No plugins found!');
            return Command::SUCCESS;
        }

        if (null !== $plugin && !empty($plugin)) {
            $results = [$plugin => $results];
        }

        $this->displayTable(
            io: $io,
            data: $results,
        );

        return Command::SUCCESS;
    }

    private function getPluginsData(
        ?string $search = null,
        ?string $plugin = null,
        bool $noCache = false,
    ): ?array {
        // params
        $args = [];
        if (null !== $search) {
            $args['search'] = $search;
        }
        if (null !== $plugin) {
            $args['plugin'] = $plugin;
        }

        /** @var KanataPlugin $pluginsApi */
        $pluginsApi = container()->get('plugins-api');

        if (!$noCache) {
            return $pluginsApi->getPlugins($args)->data;
        }

        /** @var FilesystemAdapter $cache */
        $cache = container()->get('cache');

        $cacheKey = self::PLUGINS_DATA_KEY . '-search-' . ($search ?? '') . '-plugin-' . ($plugin ?? '');

        if ($cache->hasItem($cacheKey)) {
            $cache->delete($cacheKey);
        }

        $cacheItem = $cache->getItem($cacheKey);
        $pluginsData = $cacheItem->get();
        if (null !== $pluginsData) {
            return $pluginsData;
        }

        $pluginsData = $pluginsApi->getPlugins($args)->data;
        $cacheItem->set($pluginsData);
        $cacheItem->expiresAt(Carbon::now()->addMinutes(30));
        $cache->save($cacheItem);

        return $pluginsData;
    }

    private function displayTable(SymfonyStyle $io, array $data)
    {
        $pluginSlugs = array_keys($data);

        $keys = array_keys(current($data));
        $keys[] = 'slug';

        $rows = array_map(function ($plugin, $slug) {
            $plugin['slug'] = $slug;
            return $plugin;
        }, $data, $pluginSlugs);

        $io->section('Results:');
        $io->createTable()
            ->setVertical()
            ->setHeaders($keys)
            ->setRows($rows)
            ->render();
    }
}
