<?php

namespace Kanata\Interfaces;

interface WebSocketCommunicationInterface
{
    public function get(string $action): null|array;
    public function set(string $action, string $data): void;
    public function clean(string $action): void;
    public function delete(int $id): bool;
}
