<?php

namespace Kanata\Interfaces;

interface HashInterface
{
    public static function make(string $password, string $algorithm): string;
}