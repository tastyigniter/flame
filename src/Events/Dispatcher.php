<?php namespace Igniter\Flame\Events;

class Dispatcher extends \Illuminate\Events\Dispatcher
{
    /**
     * Fire an event and call the listeners.
     *
     * @param  string|object $event
     * @param  mixed $payload
     * @param  bool $halt
     * @return array|null
     */
    public function fire($event, $payload = [], $halt = FALSE)
    {
        return $this->dispatch($event, $payload, $halt);
    }
}
