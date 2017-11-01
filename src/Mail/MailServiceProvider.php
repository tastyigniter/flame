<?php

namespace Igniter\Flame\Mail;

use Illuminate\Mail\MailServiceProvider as BaseMailServiceProvider;
use Illuminate\Mail\TransportManager;

class MailServiceProvider extends BaseMailServiceProvider
{
    public function registerSwiftTransport()
    {
        $this->app->singleton('swift.transport', function ($app) {
            $this->mergeMailerConfiguration($app);

            return new TransportManager($app);
        });
    }

    protected function mergeMailerConfiguration($app)
    {
        $setting = $app['setting']->driver('config');

        if ($protocol = $setting->get('protocol'))
            $this->app['config']->set('mail.driver', $protocol);

        if ($smtpHost = $setting->get('smtp_host'))
            $this->app['config']->set('mail.host', $smtpHost);

        if ($smtpPort = $setting->get('smtp_port'))
            $this->app['config']->set('mail.port', $smtpPort);

        if ($senderEmail = $setting->get('sender_email'))
            $this->app['config']->set('mail.form.address', $senderEmail);

        if ($senderName = $setting->get('sender_name'))
            $this->app['config']->set('mail.form.name', $senderName);

        if ($username = $setting->get('smtp_user'))
            $this->app['config']->set('mail.username', $username);

        if ($password = $setting->get('smtp_pass'))
            $this->app['config']->set('mail.password', $password);
    }
}