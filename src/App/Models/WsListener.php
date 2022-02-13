<?php

namespace Kanata\Models;

class WsListener extends Model
{
    const TABLE_NAME = 'wslisteners';
    protected string $database = self::TABLE_NAME;

    protected array $defaults = [];
}
