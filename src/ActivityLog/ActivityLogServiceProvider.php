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
        $this->commands([
            Console\Cleanup::class,
        ]);
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