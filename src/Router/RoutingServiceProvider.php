<?php namespace Igniter\Flame\Router;

use Illuminate\Routing\RoutingServiceProvider as BaseRoutingServiceProvider;
use October\Rain\Router\CoreRouter;

class RoutingServiceProvider extends BaseRoutingServiceProvider
{
    /**
     * Register the router instance.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app->singleton('router', function ($app) {
            return new CoreRouter($app['events'], $app);
        });
    }
}