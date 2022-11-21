<?php

namespace Kanata\Commands;

use Kanata\Commands\Traits\LogoTrait;
use Kanata\Services\Helpers;
use Mustache_Engine;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreatePluginCommand extends Command
{
    use LogoTrait;

    protected static $defaultName = 'plugin:create';

    protected function configure(): void
    {
        $this->setHelp('This command generate a new plugin skeleton for your Kanata Application.');

        $this->addArgument('name', InputArgument::REQUIRED, 'The plugin name.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $output->writeln('');
        $output->writeln('Kanata - Creating a Plugin');
        $output->writeln('');

        if (!Helpers::hasPluginsDbConnection()) {
            $io->error('You must start Kanata first by running vendor/bin/start-kanata');
            return Command::FAILURE;
        }

        $pluginClassName = $input->getArgument('name');

        $slug = camelToSlug($pluginClassName);
        $pluginPath = make_path_relative_to_project(trailingslashit(plugin_path()) . $slug);

        if (container()->filesystem->has($pluginPath)) {
            $io->error('Plugin\'s directory already exists.');
            return Command::FAILURE;
        }

        container()->filesystem->createDir($pluginPath);
        container()->filesystem->createDir(untrailingslashit($pluginPath) . '/src');

        $result = $this->addEmptyComposerFile($pluginPath);
        if (!$result) {
            $io->error('There was an error while trying to write composer.json file to ' . $pluginPath);
            return Command::FAILURE;
        }

        $result = $this->addBaseClass($pluginPath, $pluginClassName);
        if (!$result) {
            $io->error('There was an error while trying to write class file to ' . $pluginPath);
            return Command::FAILURE;
        }

        $io->success('Plugin Successfully created at ' . $pluginPath);
        return Command::SUCCESS;
    }

    private function addBaseClass(string $pluginPath, string $pluginClassName): bool
    {
        $stub = make_path_relative_to_project(trailingslashit($this->resolveStubDir()) . 'plugin-class.stub');
        $stubContent = container()->filesystem->read($stub);

        $mustache = new Mustache_Engine(['entity_flags' => ENT_QUOTES]);
        $parsedContent = $mustache->render($stubContent, [
            'pluginClassName' => $pluginClassName,
            'pluginDescription' => '',
            'pluginAuthorName' => '',
            'pluginAuthorEmail' => '',
        ]);

        return container()->filesystem->put(trailingslashit($pluginPath) . $pluginClassName . '.php', $parsedContent);
    }

    private function addEmptyComposerFile(string $pluginPath): bool
    {
        $stub = make_path_relative_to_project(trailingslashit($this->resolveStubDir()) . 'composer.stub');
        $stubContent = container()->filesystem->read($stub);

        return container()->filesystem->put(trailingslashit($pluginPath) . 'composer.json', $stubContent);
    }

    private function resolveStubDir(): string
    {
        return trailingslashit(__DIR__) . 'stubs';
    }
}
