<?php

namespace Kanata\Models;

class WsAssociation extends Model
{
    const TABLE_NAME = 'wsassociations';
    protected string $database = self::TABLE_NAME;

    protected array $defaults = [];
}
