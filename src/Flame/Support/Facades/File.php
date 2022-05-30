<?php

namespace Igniter\Flame\Support\Facades;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

class File extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     * @see \Igniter\Flame\Filesystem\Filesystem
     */
    protected static function getFacadeAccessor()
    {
        return 'files';
    }
}
