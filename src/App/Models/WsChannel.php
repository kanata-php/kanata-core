<?php

namespace Kanata\Models;

class WsChannel extends Model
{
    const TABLE_NAME = 'wschannels';
    protected string $database = self::TABLE_NAME;

    protected array $defaults = [];
}
