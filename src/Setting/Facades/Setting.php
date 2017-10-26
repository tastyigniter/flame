<?php

namespace Igniter\Flame\Setting\Facades;

use Igniter\Flame\Setting\SettingManager;
use Illuminate\Support\Facades\Facade as IlluminateFacade;

class Setting extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     *
     * @see \System\Libraries\Template
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return SettingManager::class;
    }
}
