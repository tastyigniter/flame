<?php

namespace Igniter\Flame\Setting;

use Igniter\Flame\Setting\Middleware\SaveSetting;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->registerManager();

        $this->registerStorage();

        $this->app->singleton(SaveSetting::class);
    }

    /**
     * Register the setting store.
     * @return void
     */
    protected function registerStorage()
    {
        $this->app['setting']->extend('config', function ($app) {
            $connectionName = $app['config']->get('database.default');
            $connection = $app['db']->connection($connectionName);

            $store = new DatabaseSettingStore($connection, 'settings', 'item', 'value');
            $store->setExtraColumns(['sort' => 'config']);

            return $store;
        });

        $this->app['setting']->extend('prefs', function ($app) {
            $connectionName = $app['config']->get('database.default');
            $connection = $app['db']->connection($connectionName);

            $store = new DatabaseSettingStore($connection, 'settings', 'item', 'value');
            $store->setExtraColumns(['sort' => 'prefs']);

            return $store;
        });
    }

    protected function registerManager()
    {
        $this->app->singleton('setting', function ($app) {
            return new SettingManager($app);
        });
    }
}
