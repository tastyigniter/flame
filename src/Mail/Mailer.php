<?php

namespace Igniter\Flame\Mail;

use InvalidArgumentException;

class Mailer extends \October\Rain\Mail\Mailer
{
    public function sendToMany($recipients, $view, array $data = [], $callback = null, $queue = FALSE)
    {
        if ($callback && !$queue && !is_callable($callback)) {
            $queue = $callback;
        }

        $method = $queue === TRUE ? 'queue' : 'send';
        $recipients = $this->processRecipients($recipients);

        foreach ($recipients as $address => $name) {
            $this->{$method}($view, $data, function ($message) use ($address, $name, $callback) {
                $message->to($address, $name);

                if (is_callable($callback)) {
                    $callback($message);
                }
            });
        }
    }

    protected function parseView($view)
    {
        if (is_string($view)) {
            return [$view, null, null];
        }

        // If the given view is an array with numeric keys, we will just assume that
        // both a "pretty" and "plain" view were provided, so we will return this
        // array as is, since it should contain both views with numerical keys.
        if (is_array($view) && isset($view[0])) {
            return [$view[0], $view[1], null];
        }

        // If this view is an array but doesn't contain numeric keys, we will assume
        // the views are being explicitly specified and will extract them via the
        // named keys instead, allowing the developers to use one or the other.
        if (is_array($view)) {

            // This is to help the Rain\Mailer::send() logic when adding raw content
            // to mail the raw value is expected to be bool
            if (isset($view['raw'])) {
                $view['text'] = $view['raw'];
                $view['raw'] = TRUE;
            }

            return [
                $view['html'] ?? null,
                $view['text'] ?? null,
                $view['raw'] ?? null,
            ];
        }

        throw new InvalidArgumentException('Invalid view.');
    }
}