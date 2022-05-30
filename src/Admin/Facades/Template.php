<?php

namespace Igniter\Admin\Facades;

use Illuminate\Support\Facades\Facade;

class Template extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @see \Igniter\System\Libraries\Template
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'admin.template';
    }
}
