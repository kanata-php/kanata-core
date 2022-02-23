<?php

namespace Kanata\Drivers;

use Illuminate\Database\Capsule\Manager;

class DbCapsule extends Manager
{
    public function closeConnection($name = 'default')
    {
        $this->container['config']['database.connections'][$name] = null;
    }
}