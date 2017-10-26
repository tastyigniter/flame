<?php

namespace Igniter\Flame\Support\Facades;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

class Str extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     *
     * @see \System\Libraries\Template
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'string';
    }
}
