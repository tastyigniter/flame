<?php

namespace Igniter\Flame\Cart\Models;

use Igniter\Flame\Database\Model;

class Cart extends Model
{
    protected static $unguarded = TRUE;

    protected $table = 'cart';

    protected $primaryKey = 'identifier';

    public $incrementing = FALSE;

    public $timestamps = TRUE;
}
