<?php

namespace Kanata\Services;

use Exception;

/**
 * This class proxy calls to helper functions.
 */
class Helpers
{
    public function __call(string $name, array $arguments)
    {
        return call_user_func_array($name, $arguments);
    }

    public static function hasPluginsDbConnection(): bool
    {
        try {
            container()->db->getConnection()->table('plugins')->first();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}