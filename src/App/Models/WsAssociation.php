<?php

namespace Kanata\Models;

class WsAssociation extends Model
{
    const TABLE_NAME = 'wsassociations';

    /** @var string */
    protected $name = self::TABLE_NAME;

    protected array $defaults = [];
}
