<?php

namespace Kanata\Models;

use Illuminate\Database\Eloquent\Model;

class WsAssociation extends Model
{
    const TABLE_NAME = 'wsassociations';

    /** @var string */
    protected $name = self::TABLE_NAME;
    protected $table = self::TABLE_NAME;

    protected array $defaults = [];
}
