<?php

declare(strict_types=1);

namespace Tests\Unit;

use Kanata\Services\Helpers;
use League\Flysystem\Filesystem;
use Mockery;
use Symfony\Component\Console\Command\Command;
use Tests\TestCase;

class PublishPluginCommandTest extends TestCase
{
    /**
     * @covers PublishPluginCommand
     * @return void
     */
    public function test_can_publish_a_directorys_config()
    {
        $this->startConsole();

        $this->mockFilesystem();
        $this->mockPluginRepo();
        $this->mockHelpers();

        $result = $this->runCommand('plugin:publish', [
            'plugin-name' => 'SamplePlugin',
            'directory' => 'config',
        ]);

        $this->assertEquals(Command::SUCCESS, $result->getStatusCode());
    }

    /**
     * @covers PublishPluginCommand
     * @return void
     */
    public function test_plugin_not_found_fail()
    {
        $this->startConsole();

        $result = $this->runCommand('plugin:publish', [
            'plugin-name' => 'SamplePlugin',
            'directory' => 'config',
        ]);
        $this->assertEquals(Command::FAILURE, $result->getStatusCode());
    }

    private function mockFilesystem()
    {
        $filesystemMock = Mockery::mock(Filesystem::class);
        $hasCounter = 0;
        $filesystemMock->shouldReceive('has')->andReturnUsing(function() use (&$hasCounter) {
            if ($hasCounter >= 2) {
                return false;
            }
            $hasCounter++;
            return true;
        });
        $filesystemMock->shouldReceive('getMimetype')->andReturn('file');
        $filesystemMock->shouldReceive('copy');
        $filesystemMock->shouldReceive('createDir');
        container()->set('filesystem', $filesystemMock);
    }

    private function mockPluginRepo()
    {
        $pluginRepoMock = Mockery::mock('alias:PluginRepository');
        $pluginRepoMock->shouldReceive('get')->andReturnUsing(function() {
            $rawPluginRecord = file_get_contents(__DIR__ . '/../Samples/user-authorization-plugin-record');
            return unserialize(base64_decode($rawPluginRecord));
        });
        container()->set('plugin-repository', $pluginRepoMock);
    }

    private function mockHelpers()
    {
        $helperMock = Mockery::mock(Helpers::class);
        $helperMock->shouldReceive('iterate_directory')->andReturnUsing(function() {
            return iterate_directory(__DIR__ . '/../Samples');
        });
        container()->set('helpers', $helperMock);
    }
}