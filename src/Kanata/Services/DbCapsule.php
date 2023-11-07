<?php

namespace Kanata\Services;

use Illuminate\Database\Capsule\Manager;

class DbCapsule extends Manager
{
    public function closeConnection($name = 'default')
    {
        $conn = $this->getConnection($name);
        $conn->disconnect();
    }
}
