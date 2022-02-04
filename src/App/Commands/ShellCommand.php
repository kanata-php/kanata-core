<?php

namespace Kanata\Commands;

use Kanata\Commands\Traits\InfoTrait;
use Kanata\Commands\Traits\LogoTrait;
use Psy\Shell;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShellCommand extends Command
{
    use InfoTrait, LogoTrait;

    protected static $defaultName = 'shell';

    protected function configure(): void
    {
        $this->setHelp('Start PsyShell for the Kanata Application.');

        $this->addArgument('script', InputArgument::OPTIONAL, 'Script to be executed.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->writeLogo($output);

        $shell = new Shell();
        $shell->setScopeVariables(get_defined_vars());
        $shell->run();
    }
}