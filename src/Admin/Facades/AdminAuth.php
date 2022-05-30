<?php

namespace Igniter\Admin\Facades;

use Illuminate\Support\Facades\Facade;

class AdminAuth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     * @see \Igniter\Flame\Auth\UserGuard
     */
    protected static function getFacadeAccessor()
    {
        return 'admin.auth';
    }
}
