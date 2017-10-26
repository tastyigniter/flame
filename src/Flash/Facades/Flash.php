<?php

namespace Igniter\Flame\Flash\Facades;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

class Flash extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     *
     * @see \System\Libraries\Template
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'flash';
    }
}