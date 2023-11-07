<?php

namespace Kanata\Drivers\Session;

use Kanata\Drivers\Session\Interfaces\SessionInterface;
use League\Flysystem\FileExistsException;
use League\Flysystem\Filesystem as Flysystem;

class Filesystem implements SessionInterface
{
    protected Flysystem $filesystem;

    protected string $sessionPath = 'storage/sessions';

    protected function getPath(): string
    {
        return $this->sessionPath . DIRECTORY_SEPARATOR;
    }

    protected function createSessionDirectory(): void
    {
        if (!$this->filesystem->has($this->getPath())) {
            $this->filesystem->createDir($this->getPath());
        }
    }

    public function __construct()
    {
        $this->filesystem = container()->get('filesystem');
        $this->createSessionDirectory();
    }

    public static function getInstance(): SessionInterface
    {
        return new self;
    }

    public function get(string $key): mixed
    {
        return $this->filesystem->read($this->getPath() . $key);
    }

    /**
     * @throws FileExistsException
     */
    public function set(string $key, mixed $data): mixed
    {
        if ($this->exists($key)) {
            return $this->filesystem->update(
                path: $this->getPath() . $key,
                contents: json_encode($data)
            );
        } else {
            return $this->filesystem->write(
                path: $this->getPath() . $key,
                contents: json_encode($data)
            );
        }
    }

    public function delete(string $key): bool
    {
        return $this->filesystem->delete($this->getPath() . $key);
    }

    public function destroy(): bool
    {
        $result = $this->filesystem->deleteDir($this->getPath());
        $this->createSessionDirectory();

        return $result;
    }

    public function exists(string $key): bool
    {
        return $this->filesystem->has($this->getPath() . $key);
    }
}
