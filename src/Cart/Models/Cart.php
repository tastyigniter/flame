<?php

namespace Igniter\Flame\Cart\Models;

use Igniter\Flame\Database\Model;

class Cart extends Model
{
    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected static $unguarded = TRUE;

    protected $table = 'cart';

    protected $primaryKey = 'identifier';

    public $incrementing = FALSE;

    public $timestamps = TRUE;
}