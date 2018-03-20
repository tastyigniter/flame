<?php

namespace Igniter\Flame\Translation\Drivers;

use Igniter\Flame\Translation\Contracts\Driver;
use Igniter\Flame\Translation\Models\Translation;

class Database implements Driver
{
    /**
     * @param $locale
     * @param $group
     * @param null $namespace
     *
     * @return mixed
     */
    public function load($locale, $group, $namespace = null)
    {
        return Translation::getCached($locale, $group, $namespace);
    }
}