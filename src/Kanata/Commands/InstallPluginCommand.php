<?php

namespace Kanata\Commands;

use Git\Console;
use Git\GitRepo;
use Kanata\Commands\Traits\LogoTrait;
use KanataPlugin\KanataPlugin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InstallPluginCommand extends Command
{
    use LogoTrait;

    protected static $defaultName = 'plugin:install';

    protected static $defaultDescription = 'This command install plugins in Kanata';

    protected function configure(): void
    {
        $this->setHelp(self::$defaultDescription)
            ->setDefinition(
                new InputDefinition([
                    new InputArgument(
                        name: 'plugin',
                        mode: InputArgument::REQUIRED,
                        description: 'The name of the plugin to be installed.',
                    ),
                ])
            );;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $plugin = $input->getArgument('plugin');

        $io->title('Plugins Install: ' . $plugin);

        $io->info('Requesting plugins info...');
        $pluginData = $this->getPluginsData($plugin);

        if (null === $pluginData) {
            $io->info('No plugins found!');
            return Command::FAILURE;
        }

        $path = untrailingslashit(plugin_path($plugin));

        if ($this->installPlugin($pluginData, $path)) {
            $io->info('Plugin installed successfully! (' . $path . ')');
            return Command::SUCCESS;
        }

        $io->info('Failed to install plugin! (' . $plugin . ')');
        return Command::FAILURE;
    }

    private function getPluginsData(string $plugin): ?array {
        // params
        $args = ['plugin' => $plugin];

        /** @var KanataPlugin $pluginsApi */
        $pluginsApi = container()->get('plugins-api');

        return $pluginsApi->getPlugins($args)->data;
    }

    private function installPlugin(array $pluginData, string $path)
    {
        $gitRepo = new GitRepo( new Console, '', true, true );
        $gitRepo->clone( $pluginData['repo'], $path );
        return file_exists($path);
    }
}
