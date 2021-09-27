<?php

namespace Igniter\Flame\Mail;

use Illuminate\Mail\MailServiceProvider as BaseMailServiceProvider;

class MailServiceProvider extends BaseMailServiceProvider
{
    protected function registerIlluminateMailer()
    {
        $this->app->singleton('mail.manager', function ($app) {
            $this->app['events']->fire('mailer.beforeRegister', [$this]);

            $mailManager = new MailManager($app);

            $this->app['events']->fire('mailer.register', [$this, $mailManager]);

            return $mailManager;
        });

        $this->app->singleton('mailer', function ($app) {
            return $app->make('mail.manager')->mailer();
        });
    }
}
