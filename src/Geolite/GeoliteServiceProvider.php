<?php

namespace Igniter\Flame\Geolite;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class GeoliteServiceProvider extends ServiceProvider
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
        $this->mergeConfigFrom(
            __DIR__.'/config/geocoder.php', 'geocoder'
        );

        $this->app->singleton('geocoder', function ($app) {
            return new Geocoder($app);
        });

        $this->app->singleton('geolite', function () {
            return new Geolite;
        });

        $aliasLoader = AliasLoader::getInstance();
        $aliasLoader->alias('Geocoder', Facades\Geocoder::class);
        $aliasLoader->alias('Geolite', Facades\Geolite::class);
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return [
            'geocoder', Geocoder::class,
            'geolite', Geolite::class
        ];
    }
}