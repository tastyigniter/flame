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
        $config = $app->make('config');

        $overrides = [
            'driver'   => $setting->get('protocol', $config->get('mail.driver')),
            'host'     => $setting->get('smtp_host') ?: $config->get('mail.host'),
            'port'     => $setting->get('smtp_port') ?: $config->get('mail.port'),
            'from'     => [
                'address' => $setting->get('sender_email') ?: $config->get('mail.from.address'),
                'name'    => $setting->get('sender_name') ?: $config->get('mail.from.name'),
            ],
            'username' => $config->get('mail.username') ?: $setting->get('smtp_user'),
            'password' => $config->get('mail.password') ?: $setting->get('smtp_pass'),
        ];

        $config->set('mail', $overrides);
    }
}