<?php

namespace Igniter\Flame\Mail;

use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->resolving('mail.manager', function ($manager, $app) {
            $this->app['events']->fire('mailer.beforeRegister', [$manager]);
        });

        $this->callAfterResolving('mail.manager', function ($manager, $app) {
            $this->app['events']->fire('mailer.register', [$this, $manager]);
        });
    }
}
