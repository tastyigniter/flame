<?php

namespace Igniter\Flame\Cart;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton('cart', function ($app) {
            $this->app['events']->fire('cart.beforeRegister', [$this]);

            $instance = new Cart($app['session'], $app['events']);

            $this->app['events']->fire('cart.afterRegister', [$instance, $this]);

            return $instance;
        });

        AliasLoader::getInstance()->alias('Cart', \Igniter\Flame\Cart\Facades\Cart::class);
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
