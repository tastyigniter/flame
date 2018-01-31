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
        $this->app->singleton('location.manager', function() {
            return new Manager();
        });

        $this->app->singleton('location.geocoder', function() {
            return new Geocoder();
        });
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return ['location.manager', 'location.geocoder'];
    }
}