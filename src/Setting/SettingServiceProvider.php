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

        $this->app->singleton(SaveSetting::class);
    }

    protected function registerManager()
    {
        $this->app->singleton(SettingManager::class, function ($app) {
            return new SettingManager($app);
        });
    }
}
