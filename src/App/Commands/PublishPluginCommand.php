<?php

namespace Kanata\Commands;

use Exception;
use Kanata\Commands\Traits\LogoTrait;
use Kanata\Models\Plugin;
use Kanata\Repositories\PluginRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PublishPluginCommand extends Command
{
    use LogoTrait;

    protected static $defaultName = 'plugin:publish';

    protected array $acceptedAssets = ['config'];

    protected function configure(): void
    {
        $this
            ->setHelp('This command activates a plugin.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('plugin-name', InputArgument::REQUIRED, 'Which plugin to publish assets.'),
                    new InputArgument('directory', InputArgument::REQUIRED, 'Directory from which assets will be published. Accepted values: config.'),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->writeLogo($output);

        $pluginName = $input->getArgument('plugin-name');
        $directory = $input->getArgument('directory');

        try {
            $this->validateDirectory($directory);
            $this->publish($pluginName, $directory);
        } catch (Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $io->success($pluginName . '\'s ' . $directory . ' asset published!');
        return Command::SUCCESS;
    }

    public function validateDirectory(string $directory)
    {
        if (!in_array($directory, $this->acceptedAssets)) {
            throw new Exception('"' . $directory . '" asset not accepted at this moment!');
        }
    }

    /**
     * @param string $pluginName
     * @param string $directory
     * @return void
     * @throws Exception
     */
    public function publish(string $pluginName, string $directory): void
    {
        $plugin = PluginRepository::get(['name' => $pluginName]);

        if (count($plugin) === 0) {
            throw new Exception('Plugin not found');
        }

        $relativePath = 'content/plugins/' . $plugin['directory_name'] . '/' . $directory;
        $destiny = $directory . '/';

        if (!container()->filesystem->has($relativePath)) {
            throw new Exception('"' . $directory . '" asset not available!');
        }

        $contents = container()->filesystem->listContents($relativePath);

        foreach ($contents as $item) {
            if (!container()->filesystem->has($destiny . $item['basename'])) {
                container()->filesystem->copy($item['path'], $destiny . $item['basename']);
            }

            if (!container()->filesystem->has($destiny . $item['basename'])) {
                throw new Exception('There was an error while trying to publish ' . $pluginName . ' plugin\'s asset ' . $directory . '.');
            }
        }
    }
}