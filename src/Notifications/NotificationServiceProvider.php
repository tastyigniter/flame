<?php

namespace Igniter\Flame\Notifications;

use Illuminate\Notifications\NotificationServiceProvider as BaseNotificationServiceProvider;

class NotificationServiceProvider extends BaseNotificationServiceProvider
{
    /**
     * Boot the application services.
     *
     * @return void
     */
    public function boot()
    {
//        $this->loadViewsFrom(__DIR__.'/resources/views', 'notifications');
//
//        if ($this->app->runningInConsole()) {
//            $this->publishes([
//                __DIR__.'/resources/views' => $this->app->resourcePath('views/vendor/notifications'),
//            ], 'laravel-notifications');
//        }
    }
}
