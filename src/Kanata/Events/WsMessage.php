<?php

namespace Kanata\Events;

use Kanata\Interfaces\EventInterface;

class WsMessage implements EventInterface
{
    public function __construct(
        public int $fd
    ){ }
}