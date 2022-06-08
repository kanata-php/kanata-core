<?php

namespace Kanata\Events;

use Kanata\Interfaces\EventInterface;

class WsClose implements EventInterface
{
    public function __construct(
        public int $fd
    ){ }
}