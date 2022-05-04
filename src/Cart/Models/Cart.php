<?php

namespace Igniter\Flame\Cart\Models;

use Igniter\Flame\Database\Model;

class Cart extends Model
{
    protected static $unguarded = true;

    protected $table = 'cart';

    protected $primaryKey = 'identifier';

    public $incrementing = false;

    public $timestamps = true;
}
