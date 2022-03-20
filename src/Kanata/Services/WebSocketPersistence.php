<?php

namespace Kanata\Services;

use Conveyor\SocketHandlers\Interfaces\PersistenceInterface;
use Kanata\Services\Traits\AssociationsPersistence;
use Kanata\Services\Traits\ChannelsPersistence;
use Kanata\Services\Traits\ListenersPersistence;

/**
 * This is the persistence between channel and FD.
 */

class WebSocketPersistence implements PersistenceInterface
{
    use ListenersPersistence;
    use ChannelsPersistence;
    use AssociationsPersistence;
}
