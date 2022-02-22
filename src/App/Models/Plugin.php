<?php

namespace Kanata\Models;

use function Stringy\create as s;

class Plugin extends Model
{
    const TABLE_NAME = 'plugins';

    /** @var string */
    protected $name = self::TABLE_NAME;
}
