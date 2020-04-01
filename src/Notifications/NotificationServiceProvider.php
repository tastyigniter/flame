<?php

namespace Igniter\Flame\Notifications;

use Illuminate\Contracts\Notifications\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Notifications\Factory as FactoryContract;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\NotificationServiceProvider as BaseNotificationServiceProvider;
use Illuminate\Support\Facades\Notification;

class NotificationServiceProvider extends BaseNotificationServiceProvider
{
    /**
     * Boot the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'notifications');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/resources/views' => $this->app->resourcePath('views/vendor/notifications'),
            ], 'laravel-notifications');
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ChannelManager::class, function ($app) {
            $this->app['events']->fire('notification.beforeRegister', [$this]);

            return new ChannelManager($app);
        });

        $this->app->alias(
            ChannelManager::class, DispatcherContract::class
        );

        $this->app->alias(
            ChannelManager::class, FactoryContract::class
        );

        Notification::resolved(function (ChannelManager $service) {
            $service->extend('mail', function ($app) {
                return $this->app->make(Channels\MailChannel::class);
            });
        });
    }
}
