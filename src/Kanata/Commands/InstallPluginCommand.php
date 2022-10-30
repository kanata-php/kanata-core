<?php

namespace Kanata\Commands;

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

        $io->info('Still work in progress...');
        return Command::SUCCESS;

        $plugin = $input->getArgument('plugin');

        $io->title('Plugins Install');

        $io->info('Requesting plugins info...');
        $pluginData = $this->getPluginsData($plugin);

        if (null === $pluginData) {
            $io->info('No plugins found!');
            return Command::FAILURE;
        }

        $this->installPlugin(array_merge($pluginData, ['slug' => $plugin]));

        return Command::SUCCESS;
    }

    private function getPluginsData(string $plugin): ?array {
        // params
        $args = ['plugin' => $plugin];

        /** @var KanataPlugin $pluginsApi */
        $pluginsApi = container()->get('plugins-api');

        return $pluginsApi->getPlugins($args)->data;
    }

    private function installPlugin(array $pluginData)
    {

    }
}
