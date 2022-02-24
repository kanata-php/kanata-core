<?php

namespace Kanata\Models;

use Illuminate\Database\Eloquent\Model;
use function Stringy\create as s;

class Plugin extends Model
{
    const TABLE_NAME = 'plugins';

    /** @var string */
    protected $name = self::TABLE_NAME;
    protected $table = self::TABLE_NAME;
}
