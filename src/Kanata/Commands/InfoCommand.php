<?php

namespace Kanata\Commands;

use Kanata\Commands\Traits\LogoTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
        $io = new SymfonyStyle($input, $output);

        $this->writeLogo($output);

        $this->printBasicInfo($io);

        return Command::SUCCESS;
    }

    private function printBasicInfo(SymfonyStyle $io): void
    {
        $io->writeln('<info>Documentation: https://kanata-php.github.io/kanata</info>');

        $io->writeln('');
        $io->writeln('<options=bold>###################################</>');
        $io->writeln('<options=bold>How to:</>');
        $io->writeln('<options=bold>###################################</>');

        $io->writeln('');
        $io->writeln('<info>To start docker-compose environment, just run:</info> docker-compose up -d');
        $io->writeln('');

        $io->writeln('<options=bold>--- Supervisor HTTP ---</>');
        $io->writeln('<info>Supervisor Output is located at:</info> storage/logs/output.log');
        $io->writeln('<info>Supervisor Errors are located at:</info> storage/logs/error.log');

        $io->writeln('');

        $io->writeln('<options=bold>--- Supervisor WebSocket ---</>');
        $io->writeln('<info>Supervisor Output is located at:</info> storage/logs/ws-output.log');
        $io->writeln('<info>Supervisor Errors are located at:</info> storage/logs/ws-error.log');

        $io->writeln('');

        $io->writeln('<options=bold>--- Inotify ---</>');
        $io->writeln('<info>Inotify Output is located at:</info> storage/logs/inotify-output.log');
        $io->writeln('<info>Inotify Errors are located at:</info> storage/logs/inotify-error.log');

        $io->writeln('');
    }
}