<?php

namespace Kanata\Interfaces;

use PhpAmqpLib\Message\AMQPMessage;

interface QueueInterface
{
    public function handle(AMQPMessage $msg, array $args = []): void;
}