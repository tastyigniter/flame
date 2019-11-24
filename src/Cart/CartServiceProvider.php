<?php

namespace Igniter\Flame\Cart;

use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = TRUE;

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/cart.php', 'cart');

        $this->app->singleton('cart', function ($app) {
            $this->app['events']->fire('cart.beforeRegister', [$this]);

            return $app->make(\Igniter\Flame\Cart\Cart::class);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['cart', Cart::class];
    }
}