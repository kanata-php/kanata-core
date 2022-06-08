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

    protected static $defaultDescription = 'Start PsyShell for the Kanata Application.';

    protected function configure(): void
    {
        $this->setHelp(self::$defaultDescription);

        $this->addArgument('script', InputArgument::OPTIONAL, 'Script to be executed.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        container()->set('context', 'shell');
        container()->set('input', $input);
        container()->set('output', $output);

        $shell = new Shell();
        $shell->setScopeVariables(get_defined_vars());
        $shell->run();

        return Command::SUCCESS;
    }
}