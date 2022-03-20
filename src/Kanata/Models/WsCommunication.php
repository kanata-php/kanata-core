<?php

namespace Kanata\Models;

use Illuminate\Database\Eloquent\Model;

class WsCommunication extends Model
{
    const TABLE_NAME = 'wscommunications';

    /** @var string */
    protected $name = self::TABLE_NAME;
    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'action',
        'data',
    ];
}
