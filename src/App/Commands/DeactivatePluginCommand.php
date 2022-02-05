<?php

namespace Kanata\Commands;

use Kanata\Commands\Traits\LogoTrait;
use Kanata\Models\Plugin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeactivatePluginCommand extends Command
{
    use LogoTrait;

    protected static $defaultName = 'plugin:deactivate';

    protected function configure(): void
    {
        $this
            ->setHelp('This command deactivates a plugin.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('plugin-name', InputArgument::REQUIRED, 'Which plugin to deactivate.'),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->writeLogo($output);

        $pluginName = $input->getArgument('plugin-name');

        $plugin = Plugin::getInstance()->where('directory_name', '=', $pluginName)->find();

        if ($plugin->count() === 0) {
            $io->error('Plugin ' . $pluginName . ' was not found!');
            return Command::FAILURE;
        }

        if (!$plugin->update(['active' => false])) {
            $io->error('There was an error while trying to activate Plugin ' . $pluginName . '.');
            return Command::FAILURE;
        }

        $io->success('Plugin ' . $pluginName . ' deactivated!');
        return Command::SUCCESS;
    }
}