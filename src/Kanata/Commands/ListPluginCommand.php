<?php

namespace Kanata\Commands;

use Kanata\Commands\Traits\LogoTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListPluginCommand extends Command
{
    use LogoTrait;

    protected static $defaultName = 'plugin:list';

    protected function configure(): void
    {
        $this->setHelp('List available plugins.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->writeLogo($output);

        $plugins = array_map(function ($plugin) use ($io) {
            $plugin = array_only($plugin, ['name', 'active', 'directory_name']);
            $plugin['active'] = $plugin['active'] === 0 ? 'false' : 'true';
            return $plugin;
        }, get_plugins());

        if (count($plugins) === 0) {
            $io->success('No Plugin found!');
            return Command::SUCCESS;
        }

        $io->table(array_keys($plugins[0]), array_map(function ($plugin) {
            return array_values($plugin);
        }, $plugins));

        return Command::SUCCESS;
    }
}