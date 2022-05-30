<?php

namespace Igniter\Flame\Mail;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->resolving('mail.manager', function ($manager, $app) {
            $this->app['events']->fire('mailer.beforeRegister', [$manager]);
        });

        $this->callAfterResolving('mail.manager', function ($manager, $app) {
            $this->app['events']->fire('mailer.register', [$this, $manager]);
        });

//        $this->app->singleton('mail.manager', function ($app) {
//            $this->app['events']->fire('mailer.beforeRegister', [$this]);
//
//            $mailManager = new MailManager($app);
//
//            $this->app['events']->fire('mailer.register', [$this, $mailManager]);
//
//            return $mailManager;
//        });

//        $this->app->singleton('mailer', function ($app) {
//            return $app->make('mail.manager')->mailer();
//        });
    }
}
