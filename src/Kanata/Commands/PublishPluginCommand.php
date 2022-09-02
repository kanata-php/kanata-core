<?php

namespace Kanata\Commands;

use Exception;
use Kanata\Commands\Traits\LogoTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PublishPluginCommand extends Command
{
    use LogoTrait;

    const CONFIG_DIR = 'config';
    const VIEWS_DIR = 'views';

    protected static $defaultName = 'plugin:publish';

    protected array $acceptedAssets = [
        self::CONFIG_DIR,
        self::VIEWS_DIR,
    ];

    protected function configure(): void
    {
        $this
            ->setHelp('This command publishes assets from plugin.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('plugin-name', InputArgument::REQUIRED, 'Which plugin to publish assets.'),
                    new InputArgument('directory', InputArgument::REQUIRED, 'Directory from which assets will be published. Accepted values: ' . implode(', ', $this->acceptedAssets) . '.'),
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
            $this->publish(
                pluginName: $pluginName,
                directory: $directory,
            );
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
        $pluginRepo = container()->get('plugin-repository');
        $plugin = $pluginRepo::get(['name' => $pluginName]);

        if (count($plugin) === 0) {
            throw new Exception('Plugin not found');
        }

        $relativePath = 'content/plugins/' . $plugin['directory_name'] . '/' . $directory;
        $destiny = $this->getDestinationDirectory(
            pluginName: $pluginName,
            directory: $directory,
        );

        if (!container()->filesystem->has($relativePath)) {
            throw new Exception('"' . $directory . '" asset not available!');
        }

        $iterator = container()->helpers->iterate_directory($relativePath);
        foreach ($iterator as $item) {
            $focus = $destiny . DIRECTORY_SEPARATOR . $iterator->getSubPathname();

            // create if not exists
            $currentDestinationDirectory = pathinfo($focus, PATHINFO_DIRNAME);
            $currentRelativeDestinationDirectory = make_path_relative_to_project(
                $currentDestinationDirectory
            );

            if (!container()->filesystem
                ->has($currentRelativeDestinationDirectory)) {
                container()->filesystem->createDir($currentRelativeDestinationDirectory);
            }

            $currentRelativeDestinationFile = $currentRelativeDestinationDirectory . DIRECTORY_SEPARATOR . $item->getFilename();

            // conclude creation
            $focusFileMimetype = container()->filesystem->getMimetype($item->getPathName());
            if (
                'directory' !== $focusFileMimetype
                && !container()->filesystem->has($currentRelativeDestinationFile)
            ) {
                container()->filesystem->copy($item->getPathName(), $currentRelativeDestinationFile);
            }
        }
    }

    private function getDestinationDirectory(string $pluginName, string $directory): string
    {
        switch ($directory) {
            case self::VIEWS_DIR:
                return resource_path() . self::VIEWS_DIR .
                    DIRECTORY_SEPARATOR . 'vendors' .
                    DIRECTORY_SEPARATOR . camelToSlug($pluginName) .
                    DIRECTORY_SEPARATOR . $directory;

            case self::CONFIG_DIR:
            default:
                return $directory;
        }
    }
}