<?php

namespace Igniter\Flame\Mixins;

use Igniter\System\Mail\TemplateMailable;

class Mail
{
    public function sendTemplate()
    {
        return function ($view, $vars, $to = []) {
            /** @var \Illuminate\Mail\Mailer $this */
            return $this->queue(TemplateMailable::create($view)
                ->with($vars)
                ->to($to));
        };
    }

    public function queueTemplate()
    {
        return function ($view, $vars, $to = []) {
            /** @var \Illuminate\Mail\Mailer $this */
            return $this->queue(TemplateMailable::create($view)
                ->with($vars)
                ->to($to));
        };
    }
}
