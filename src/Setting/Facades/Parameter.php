<?php

namespace Igniter\Flame\Setting\Facades;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

class Parameter extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     *
     * @see \System\Libraries\Template
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'system.parameter';
    }
}
