<?php

namespace Kanata\Commands;

use Kanata\Commands\Traits\LogoTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends Command
{
    use LogoTrait;

    protected static $defaultName = 'info';

    protected static $defaultDescription = 'This command gives you information about your Kanata Application.';

    protected function configure(): void
    {
        $this->setHelp(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->writeLogo($output);

        $this->printBasicInfo($output);

        return Command::SUCCESS;
    }

    private function printBasicInfo(OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln('<options=bold>###################################</>');
        $output->writeln('<options=bold>How to:</>');
        $output->writeln('<options=bold>###################################</>');

        $output->writeln('');
        $output->writeln('<info>To start docker-compose environment, just run:</info> docker-compose up -d');
        $output->writeln('');

        $output->writeln('<options=bold>--- Supervisor HTTP ---</>');
        $output->writeln('<info>Supervisor Output is located at:</info> storage/logs/output.log');
        $output->writeln('<info>Supervisor Errors are located at:</info> storage/logs/error.log');

        $output->writeln('');

        $output->writeln('<options=bold>--- Supervisor WebSocket ---</>');
        $output->writeln('<info>Supervisor Output is located at:</info> storage/logs/ws-output.log');
        $output->writeln('<info>Supervisor Errors are located at:</info> storage/logs/ws-error.log');

        $output->writeln('');

        $output->writeln('<options=bold>--- Inotify ---</>');
        $output->writeln('<info>Inotify Output is located at:</info> storage/logs/inotify-output.log');
        $output->writeln('<info>Inotify Errors are located at:</info> storage/logs/inotify-error.log');

        $output->writeln('');
    }
}