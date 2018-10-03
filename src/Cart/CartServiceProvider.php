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
        $this->app->bind('cart', \Igniter\Flame\Cart\Cart::class);

        $this->mergeConfigFrom(__DIR__.'/config/cart.php', 'cart');
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