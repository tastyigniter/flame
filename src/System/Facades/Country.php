<?php

namespace Igniter\System\Facades;

use Illuminate\Support\Facades\Facade;

class Country extends Facade
{
    /**
     * Get the registered name of the component.
     * @see \Igniter\System\Libraries\Country
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'country';
    }
}
