<?php

namespace Kanata\Commands;

use Psy\Shell;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShellCommand extends Command
{
    protected static $defaultName = 'shell';

    protected function configure(): void
    {
        $this->setHelp('Start PsyShell for the Kanata Application.');

        $this->addArgument('script', InputArgument::OPTIONAL, 'Script to be executed.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $shell = new Shell();
        $shell->setScopeVariables(get_defined_vars());
        $shell->run();

        return Command::SUCCESS;
    }
}