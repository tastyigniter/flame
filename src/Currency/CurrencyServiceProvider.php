<?php

namespace Igniter\Flame\Currency;

use Igniter\Flame\Currency\Middleware\CurrencyMiddleware;
use Illuminate\Support\ServiceProvider;

class CurrencyServiceProvider extends ServiceProvider
{
    protected $defer = TRUE;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/currency.php', 'currency'
        );

        $this->app[\Illuminate\Routing\Router::class]->aliasMiddleware('currency', CurrencyMiddleware::class);

        $this->registerCurrency();
        $this->registerCurrencyCommands();
    }

    /**
     * Register currency provider.
     *
     * @return void
     */
    public function registerCurrency()
    {
        $this->app->singleton('currency', function ($app) {
            return new Currency(
                $app->config->get('currency', []),
                $app['cache']
            );
        });
    }

    /**
     * Register currency commands.
     *
     * @return void
     */
    public function registerCurrencyCommands()
    {
        $this->commands([
            Console\Cleanup::class,
            Console\Update::class,
        ]);
    }

    public function provides()
    {
        return ['currency', Currency::class];
    }
}