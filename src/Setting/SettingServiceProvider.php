<?php

namespace Igniter\Flame\Setting;

use Igniter\Flame\Setting\Middleware\SaveSetting;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    protected $defer = TRUE;

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->registerManager();

        $this->registerStores();

        $this->app->singleton(SaveSetting::class);
    }

    protected function registerManager()
    {
        $this->app->singleton('setting.manager', function ($app) {
            return new SettingManager($app);
        });
    }

    protected function registerStores()
    {
        $this->app->singleton('system.setting', function ($app) {
            return $app['setting.manager']->driver();
        });

        $this->app->singleton('system.parameter', function ($app) {
            return $app['setting.manager']->driver('prefs');
        });
    }

    public function provides()
    {
        return ['setting.manager', 'system.setting', 'system.parameter', SaveSetting::class];
    }
}
