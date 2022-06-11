<?php

namespace Igniter\Flame\Mixins;

use Igniter\System\Mail\TemplateMailable;

class Mail
{
    public function rawTemplate()
    {
        return function ($text, $vars, $callback = null) {
            /** @var \Illuminate\Mail\Mailer $this */
            return $this->send(TemplateMailable::createFromRaw($text)->applyCallback($callback)->with($vars));
        };
    }

    public function sendTemplate()
    {
        return function ($view, $vars, $callback = null) {
            /** @var \Illuminate\Mail\Mailer $this */
            return $this->send(TemplateMailable::create($view)->applyCallback($callback)->with($vars));
        };
    }

    public function queueTemplate()
    {
        return function ($view, $vars, $callback = null) {
            /** @var \Illuminate\Mail\Mailer $this */
            return $this->queue(TemplateMailable::create($view)->applyCallback($callback)->with($vars));
        };
    }
}
