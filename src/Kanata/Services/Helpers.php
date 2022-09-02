<?php

namespace Kanata\Services;

/**
 * This class proxy calls to helper functions.
 */
class Helpers
{
    public function __call(string $name, array $arguments)
    {
        return call_user_func_array($name, $arguments);
    }
}