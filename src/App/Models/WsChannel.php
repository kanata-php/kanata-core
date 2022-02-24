<?php

namespace Kanata\Models;

use Illuminate\Database\Eloquent\Model;

class WsChannel extends Model
{
    const TABLE_NAME = 'wschannels';

    /** @var string */
    protected $name = self::TABLE_NAME;
    protected $table = self::TABLE_NAME;

    protected array $defaults = [];
}
