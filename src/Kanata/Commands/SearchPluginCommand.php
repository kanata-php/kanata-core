<?php

namespace Kanata\Commands;

use Exception;
use Kanata\Commands\Traits\LogoTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SearchPluginCommand extends Command
{
    use LogoTrait;

    protected static $defaultName = 'plugin:search';

    protected static $defaultDescription = 'This command search for plugins for Kanata Application';

    protected function configure(): void
    {
        $this->setHelp(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        throw new Exception('Not implemented!');
        return Command::SUCCESS;
    }
}
