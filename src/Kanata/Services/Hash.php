<?php

namespace Kanata\Services;

use Kanata\Interfaces\HashInterface;

class Hash implements HashInterface
{
    public static function make(string $password, string $algorithm = PASSWORD_BCRYPT): string
    {
        return password_hash($password, $algorithm);
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}