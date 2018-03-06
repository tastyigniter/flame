<?php

namespace Igniter\Flame\Location;

class Geocoder extends \Illuminate\Support\Manager
{
    /**
     * Get the default driver name.
     * @return string
     */
    public function getDefaultDriver()
    {
        return 'google';
    }
}