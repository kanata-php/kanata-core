<?php

namespace Kanata\Models;

use function Stringy\create as s;

class Plugin extends Model
{
    const TABLE_NAME = 'plugins';

    /** @var string */
    protected $name = self::TABLE_NAME;

    public function getClassName(): string
    {
        return ucfirst((string) s($this->directory_name)->camelize());
    }

    /**
     * Plugin has a main file that has to be capital/camel case of the directory, or "index.php".
     *
     * @return ?string
     */
    public function getMainFile(): ?string
    {
        $pluginPath = trailingslashit($this->path);

        $mainFileFullPath = $pluginPath . $this->getClassName() . '.php';
        if (file_exists($mainFileFullPath)) {
            return $mainFileFullPath;
        }

        $indexFileFullPath = $pluginPath . 'index.php';
        if (file_exists($indexFileFullPath)) {
            return $indexFileFullPath;
        }

        return null;
    }
}
