<?php

namespace Kanata\Commands;

use Kanata\Commands\Traits\LogoTrait;
use Mustache_Engine;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Mustachio\Service as Stache;

class SetUpDbCommand extends Command
{
    use LogoTrait;

    const DB_SQLITE = 'sqlite';
    const DB_MYSQL = 'mysql';
    const DB_OPTIONS = [
        self::DB_SQLITE,
        // self::DB_MYSQL,
    ];

    protected static $defaultName = 'db:set-up';

    protected static $defaultDescription = 'This command prepares the environment for databases.';

    protected function configure(): void
    {
        $this
            ->setHelp(self::$defaultDescription)
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('name', InputArgument::REQUIRED, 'The db type. Can be one of: ' . implode(', ', self::DB_OPTIONS)),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $output->writeln('');
        $output->writeln('Kanata - Setting up a db for Kanata');
        $output->writeln('');

        $dbType = $input->getArgument('name');

        if (null === $dbType) {
            $io->error('Missing command name argument!');
            return Command::FAILURE;
        }

        if (!in_array($dbType, self::DB_OPTIONS)) {
            $io->error('DB type must be one of: ' . implode(', ', self::DB_OPTIONS) . '!');
            return Command::FAILURE;
        }

        if ($dbType === self::DB_SQLITE) {
            return $this->prepareSqlite($io);
        }

        $io->error('Db Type is probably not accepted: ' . $dbType);
        return Command::FAILURE;
    }

    private function prepareSqlite(SymfonyStyle $io): int
    {
        $fs = container()->filesystem;

        $dbPath = DIRECTORY_SEPARATOR . 'content' . 
            DIRECTORY_SEPARATOR . 'database' . 
            DIRECTORY_SEPARATOR . 'database.sqlite';
        $dbFullPath = untrailingslashit(base_path()) . $dbPath;

        if ($fs->has($dbPath)) {
            $io->info('Database file already exists: ' . $dbPath);
        }

        if (!$fs->has($dbPath) && !$fs->write($dbPath, '')) {
            $io->error('Failed to write file: ' . $dbPath);
            return Command::FAILURE;
        }

        // update .env
        
        $env1 = 'DB_DRIVER';
        $env2 = 'DB_DATABASE';

        Stache::replaceFileLineByCondition(
            file: base_path('.env'),
            conditions: [
                fn($l) => strpos($l, $env1) === 0,
                fn($l) => strpos($l, $env2) === 0,
            ],
            values: [
                $env1 . '=sqlite',
                $env2 . '=' . $dbFullPath,
            ],
            toRemove: function ($l) {
                return strpos($l, 'DB_HOST') === 0
                    || strpos($l, 'DB_PORT') === 0
                    || strpos($l, 'DB_USERNAME') === 0
                    || strpos($l, 'DB_PASSWORD') === 0;
            },
        );

        return Command::SUCCESS;
    }
}
