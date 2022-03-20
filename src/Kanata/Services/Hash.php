<?php

namespace Kanata\Services;

use Kanata\Interfaces\HashInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class Hash implements HashInterface
{
    const ALGORITHM_BCRYPT = 'bcrypt';

    private static function getHasher(string $algorithm): PasswordHasherInterface
    {
        $factory = new PasswordHasherFactory([
            $algorithm => ['algorithm' => $algorithm],
        ]);

        return $factory->getPasswordHasher($algorithm);
    }

    public static function make(string $password, string $algorithm = self::ALGORITHM_BCRYPT): string
    {
        return self::getHasher($algorithm)->hash($password);
    }
}