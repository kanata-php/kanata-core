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

class CreateCommand extends Command
{
    use LogoTrait;

    protected static $defaultName = 'command:create';

    protected static $defaultDescription = 'This command generate a new command skeleton for your Kanata Plugin';

    protected function configure(): void
    {
        $this
            ->setHelp(self::$defaultDescription)
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('name', InputArgument::REQUIRED, 'The command name.'),
                    new InputOption('plugin', null, InputOption::VALUE_REQUIRED, 'The plugin for the command to be in.'),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $output->writeln('');
        $output->writeln('Kanata - Creating a Command');
        $output->writeln('');

        $commandName = $input->getArgument('name');
        $pluginClassName = $input->getOption('plugin');

        if (null === $commandName) {
            $io->error('Missing command name argument!');
            return Command::FAILURE;
        }

        if (null === $pluginClassName) {
            $io->error('Missing plugin name option!');
            return Command::FAILURE;
        }

        $slug = camelToSlug($pluginClassName);
        $pluginPath = make_path_relative_to_project(trailingslashit(plugin_path()) . $slug);
        $commandsPath = trailingslashit($pluginPath) . 'src/Commands';

        if (!container()->filesystem->has($pluginPath)) {
            $io->error('Plugin doesn\'t exist.');
            return Command::FAILURE;
        }

        if (!container()->filesystem->has($commandsPath)) {
            container()->filesystem->createDir($commandsPath);
        }

        $result = $this->addBaseClass($commandsPath, $commandName, $pluginClassName);
        if (!$result) {
            $io->error('There was an error while trying to write class file to ' . $pluginPath);
            return Command::FAILURE;
        }

        $io->success('Command Successfully created at ' . $commandsPath . '/' . $commandName . '.php');
        $io->info('Don\'t forget to register your command through the hook "commands" !');
        $io->info('Find more information here: https://github.com/kanata-php/kanata#commands');
        return Command::SUCCESS;
    }

    private function addBaseClass(string $commandsPath, string $commandName, string $pluginClassName): bool
    {
        $stub = make_path_relative_to_project(trailingslashit($this->resolveStubDir()) . 'command-class.stub');
        $stubContent = container()->filesystem->read($stub);

        $mustache = new Mustache_Engine(['entity_flags' => ENT_QUOTES]);
        $parsedContent = $mustache->render($stubContent, [
            'commandClassName' => $commandName,
            'pluginClassName' => $pluginClassName
        ]);

        return container()->filesystem->put(trailingslashit($commandsPath) . $commandName . '.php', $parsedContent);
    }

    private function resolveStubDir(): string
    {
        return trailingslashit(__DIR__) . 'stubs';
    }
}
