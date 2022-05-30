<?php

namespace Igniter\System\Facades;

use Illuminate\Support\Facades\Facade;

class Assets extends Facade
{
    /**
     * Get the registered name of the component.
     * @see \Igniter\System\Libraries\Template
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'assets';
    }
}
