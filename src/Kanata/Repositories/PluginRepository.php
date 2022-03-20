<?php

namespace Kanata\Repositories;

use Error;
use Illuminate\Database\Eloquent\Collection;
use Kanata\Models\Plugin;
use Kanata\Models\Traits\Validation;
use Kanata\Repositories\Interfaces\Repository;
use Exception;
use Lazer\Classes\LazerException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;
use Lazer\Classes\Database as Lazer;
use function Stringy\create as s;

class PluginRepository implements Repository
{
    use Validation;

    public array $errors = [];

    public array $defaultValues = [
        'active' => false,
    ];

    public static function all(): Collection
    {
        return Plugin::all();
    }

    public static function get(array $params): array
    {
        $searching = false;

        if (!empty($params)) {
            $plugins = new Plugin;
        }

        if (isset($params['name'])) {
            $searching = true;
            $plugins = $plugins->where('name', '=', $params['name']);
        }

        if (isset($params['active'])) {
            $searching = true;
            $plugins = $plugins->where('active', '=', true);
        }

        if ($searching && null !== $plugins->first()) {
            return $plugins->first()->toArray();
        }

        return [];
    }

    public static function delete(int $id)
    {
        try {
            Plugin::find($id)->delete();
        } catch (Exception|Error $e) {
            // --
        }
    }

    /**
     * @param string $procedure
     * @param array $data
     * @return void
     * @throws Exception
     */
    private function validate(string $procedure, array $data): void
    {
        if (isset($data['name'])) {
            $this->validateField($data['name'], [new Type('string'), new NotBlank()]);
        }

        if ($procedure === 'create' || isset($data['directory_name'])) {
            $this->validateField($data['directory_name'], [new Required(), new Type('string'), new NotBlank()]);
        }

        if (isset($data['author_name'])) {
            $this->validateField($data['author_name'], [new Type('string'), new NotBlank()]);
        }

        if (isset($data['author_email'])) {
            $this->validateField($data['author_email'], [new Type('string'), new NotBlank()]);
        }

        if (isset($data['description'])) {
            $this->validateField($data['description'], [new Type('string'), new NotBlank()]);
        }

        if ($procedure === 'create' || isset($data['path'])) {
            $this->validateField($data['path'], [new Required(), new NotBlank()]);
        }
    }

    public function registerPlugin(array $data): ?Plugin
    {
        try {
            $this->validate('create', $data);
        } catch (Exception $e) {
            $this->errors = explode('|', $e->getMessage());
            return null;
        }

        $record = Plugin::where('directory_name', '=', $data['directory_name'])->first();

        if (null !== $record && null !== $record['directory_name']) {
            return $record;
        }

        // here we delete in order to register again correctly
        if (null !== $record) {
            self::delete($record['id']);
        }

        $data = $this->fillDefaults($data);

        return Plugin::create($data);
    }

    public function fillDefaults(array $data): array
    {
        foreach ($this->defaultValues as $key => $value) {
            if (!isset($data[$key])) {
                $data[$key] = $value;
            }
        }
        return $data;
    }

    public function updatePlugin(int $id, array $data): bool
    {
        try {
            $this->validate('update', $data);
        } catch (Exception $e) {
            $this->errors = explode('|', $e->getMessage());
            return false;
        }

        try {
            $record = Plugin::find((int) $id);
        } catch (LazerException $e) {
            return false;
        }

        return $record->update($data);
    }

    public function getClassName(Plugin $plugin): string
    {
        return ucfirst((string) s($plugin->directory_name)->camelize());
    }

    /**
     * Plugin has a main file that has to be capital/camel case of the directory, or "index.php".
     *
     * @param Plugin $plugin
     *
     * @return ?string
     */
    public function getMainFile(Plugin $plugin): ?string
    {
        $pluginPath = trailingslashit($plugin->path);

        $mainFileFullPath = $pluginPath . $this->getClassName($plugin) . '.php';
        if (file_exists($mainFileFullPath)) {
            return $mainFileFullPath;
        }

        $indexFileFullPath = $pluginPath . 'index.php';
        if (file_exists($indexFileFullPath)) {
            return $indexFileFullPath;
        }

        return null;
    }

    public function registerIfNotRegistered(string $pluginPath): Plugin
    {
        $pluginDirectoryName = basename($pluginPath);
        $record = $this->registerPlugin([
            'name' => slugToCamel($pluginDirectoryName),
            'directory_name' => $pluginDirectoryName,
            'path' => $pluginPath,
        ]);

        if ($record->path !== $pluginPath) {
            self::updatePlugin($record->id, [
                'path' => $pluginPath,
            ]);
        }

        return $record;
    }
}