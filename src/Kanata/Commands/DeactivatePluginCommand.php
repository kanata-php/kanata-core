<?php

namespace Kanata\Commands;

use Exception;
use Kanata\Commands\Traits\LogoTrait;
use Kanata\Commands\Traits\PluginHelpersTrait;
use Kanata\Services\Helpers;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeactivatePluginCommand extends Command
{
    use LogoTrait, PluginHelpersTrait;

    protected static $defaultName = 'plugin:deactivate';

    protected function configure(): void
    {
        $this
            ->setHelp('This command deactivates a plugin.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('plugin-name', InputArgument::OPTIONAL, 'Which plugin to deactivate.'),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->writeLogo($output);

        if (!Helpers::hasPluginsDbConnection()) {
            $io->error('You must start Kanata first by running vendor/bin/start-kanata');
            return Command::FAILURE;
        }

        $pluginName = $input->getArgument('plugin-name');

        if (null === $pluginName) {
            $pluginName = $this->interactForPluginName($io);
        }

        try {
            $result = deactivate_plugin($pluginName);
        } catch (Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if (!$result) {
            $io->error('There was an error while trying to activate Plugin ' . $pluginName . '.');
            return Command::FAILURE;
        }

        $io->success('Plugin ' . $pluginName . ' deactivated!');
        return Command::SUCCESS;
    }
}