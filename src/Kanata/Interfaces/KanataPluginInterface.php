<?php

namespace Kanata\Interfaces;

/**
 * Interface KanataPluginInterface
 */

interface KanataPluginInterface
{
    /**
     * Method executed when loading the plugin.
     *
     * @return void
     */
    public function start(): void;
}