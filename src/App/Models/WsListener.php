<?php

namespace Kanata\Models;

class WsListener extends Model
{
    const TABLE_NAME = 'wslisteners';

    /** @var string */
    protected $name = self::TABLE_NAME;

    protected array $defaults = [];
}
