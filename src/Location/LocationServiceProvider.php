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
        $this->registerGeocoder();

        $this->registerGoogleGeocoder();

        $this->registerManager();

        $this->app->alias('location', Manager::class);
    }

    protected function registerGeocoder()
    {
        $this->app->singleton('geocoder', function ($app) {
            return new Geocoder($app);
        });
    }

    protected function registerGoogleGeocoder()
    {
        $this->app['geocoder']->extend('google', function () {
            return new GoogleGeocoder();
        });
    }

    protected function registerManager()
    {
        $this->app->singleton('location', function ($app) {
            return new Manager($app['session.store'], $app['events']);
        });
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return ['location', Manager::class];
    }
}