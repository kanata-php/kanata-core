<?php

namespace Kanata\Models;

class WsChannel extends Model
{
    const TABLE_NAME = 'wschannels';

    /** @var string */
    protected $name = self::TABLE_NAME;

    protected array $defaults = [];
}
