<?php

namespace Kanata\Commands\Traits;

use Symfony\Component\Console\Style\SymfonyStyle;

trait PluginHelpersTrait
{
    public function interactForPluginName(SymfonyStyle $io): string
    {
        $plugins = array_map(function ($plugin) use ($io) {
            $plugin = array_only($plugin, ['id', 'name', 'active']);
            $plugin['active'] = $plugin['active'] === 0 ? 'false' : 'true';
            return $plugin;
        }, get_plugins());

        $pluginsForAnswer = array_combine(array_column($plugins, 'id'), $plugins);

        $selected = false;
        while (!$selected) {
            $io->table(array_keys($plugins[0]), array_map(function ($plugin) {
                return array_values($plugin);
            }, $plugins));
            $answer = $io->ask("Which plugin you wish to activate? (id)");
            if (isset($pluginsForAnswer[$answer])) {
                $selected = true;
                continue;
            }

            $io->error('Couldn\'t find plugin with id "' . $answer . '". Please select again.');
        }

        return $pluginsForAnswer[$answer]['name'];
    }
}