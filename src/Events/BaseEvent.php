<?php

namespace Igniter\Flame\Events;

use Event;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function fireBackwardsCompatibleEvent($name, $params = null)
    {
        Event::fire($name, $params);
    }
}
