<?php

namespace Kanata\Drivers\Session\Interfaces;

interface SessionInterface
{
    public static function getInstance(): SessionInterface;

    public function get(string $key): mixed;

    public function set(string $key, mixed $data): mixed;

    public function delete(string $key): bool;

    public function destroy(): bool;

    public function exists(string $key): bool;
}
