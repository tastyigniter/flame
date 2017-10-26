<?php

namespace Igniter\Flame\ActivityLog;

use Illuminate\Support\ServiceProvider;

/**
 * Class ActivityLogServiceProvider
 */
class ActivityLogServiceProvider extends ServiceProvider
{
    public $defer = TRUE;

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->app->singleton('activitylog', function ($app) {

            $auth = (!$app->runningInAdmin())
                ? $app['main.auth']->user()
                : $app['admin.auth']->user();

            $logger = new ActivityLogger();
            $logger->setAuthDriver($auth);

            return $logger;
        });
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return ['activitylog', ActivityLogger::class];
    }
}