<?php

namespace Igniter\Flame\Location;

use Illuminate\Support\ServiceProvider;

class LocationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = TRUE;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        traceLog('Deprecated. See Igniter\Flame\Geolite\GeoliteServiceProvider or Igniter\Local\Extension');
    }
}
