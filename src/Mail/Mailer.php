<?php

namespace Igniter\Flame\Mail;

use October\Rain\Mail\Mailer as BaseMailer;

class Mailer extends BaseMailer
{
    public function sendToMany($recipients, $view, array $data = [], $callback = null, $queue = false)
    {
        if ($callback && !$queue && !is_callable($callback)) {
            $queue = $callback;
        }

        $method = $queue === true ? 'queue' : 'send';
        $recipients = $this->processRecipients($recipients);

        foreach ($recipients as $address => $name) {
            $this->{$method}($view, $data, function($message) use ($address, $name, $callback) {

                $message->to($address, $name);

                if (is_callable($callback)) {
                    $callback($message);
                }
            });
        }

    }
}