<?php

namespace Kanata\Events;

use Kanata\Interfaces\EventInterface;

class WsOpen implements EventInterface
{
    public function __construct(
        public int $fd
    ){ }
}