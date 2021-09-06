<?php

namespace Igniter\Flame\Mail;

use Illuminate\Mail\MailServiceProvider as BaseMailServiceProvider;

class MailServiceProvider extends BaseMailServiceProvider
{
    protected function registerIlluminateMailer()
    {
        $this->app->singleton('mail.manager', function ($app) {
            return new MailManager($app);
        });

        $this->app->singleton('mailer', function ($app) {
            $this->app['events']->fire('mailer.beforeRegister', [$this]);

            $mailer = $app->make('mail.manager')->mailer();

            $this->app['events']->fire('mailer.register', [$this, $mailer]);

            return $mailer;
        });
    }
}
