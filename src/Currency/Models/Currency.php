<?php

namespace Igniter\Flame\Currency\Models;

use Igniter\Flame\Currency\Contracts\CurrencyInterface;
use Igniter\Flame\Database\Model;

abstract class Currency extends Model implements CurrencyInterface
{
    /**
     * @var string The database table name
     */
    protected $table = 'currencies';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'id';

    public function scopeWhereIsEnabled($query)
    {
        return $query->where('is_enabled', 1);
    }

    public function getFormat()
    {
        return '1,0.00';
    }

    public function updateRate($currencyRate)
    {
        $this->rate = $currencyRate;
        $this->save();
    }
}