<?php

namespace Igniter\Flame\Traits;

use Event;

/**
 * Adds event related features to any class.
 *
 * @package Igniter\Traits
 */
trait EventEmitter
{
    /**
     * @var array Collection of registered events to be fired once only.
     */
    protected $emitterSingleEvents = [];

    /**
     * @var array Collection of registered events.
     */
    protected $emitterEvents = [];

    /**
     * @var array Sorted collection of events.
     */
    protected $emitterEventSorted = [];

    /**
     * Create a new event binding.
     *
     * @param string $event The event name to listen for
     * @param callable $callback The callback to call when emitted
     * @param int $priority
     *
     * @return static
     */
    public function bindEvent($event, $callback, $priority = 0)
    {
        $this->emitterEvents[$event][$priority][] = $callback;
        unset($this->emitterEventSorted[$event]);

        return $this;
    }

    /**
     * Create a new event binding that fires once only
     *
     * @param string $event The event name
     * @param callable $callback
     *
     * @return static
     */
    public function bindEventOnce($event, $callback)
    {
        $this->emitterSingleEvents[$event][] = $callback;

        return $this;
    }

    /**
     * Sort the listeners for a given event by priority.
     *
     * @param  string $eventName
     *
     * @return void
     */
    protected function emitterEventSortEvents($eventName)
    {
        $this->emitterEventSorted[$eventName] = [];

        if (isset($this->emitterEvents[$eventName])) {
            krsort($this->emitterEvents[$eventName]);

            $this->emitterEventSorted[$eventName] = call_user_func_array('array_merge', $this->emitterEvents[$eventName]);
        }
    }

    /**
     * Destroys an event binding.
     *
     * @param string $event Event to destroy
     *
     * @return self
     */
    public function unbindEvent($event = null)
    {
        // Multiple events
        if (is_array($event)) {
            foreach ($event as $_event) {
                $this->unbindEvent($_event);
            }

            return;
        }

        if ($event === null) {
            unset($this->emitterSingleEvents);
            unset($this->emitterEvents);
            unset($this->emitterEventSorted);

            return $this;
        }

        if (isset($this->emitterSingleEvents[$event]))
            unset($this->emitterSingleEvents[$event]);

        if (isset($this->emitterEvents[$event]))
            unset($this->emitterEvents[$event]);

        if (isset($this->emitterEventSorted[$event]))
            unset($this->emitterEventSorted[$event]);

        return $this;
    }

    /**
     * Fire an event and call the listeners.
     *
     * @param string $event Event name
     * @param array $params Event parameters
     * @param boolean $halt Halt after first non-null result
     *
     * @return string|array Collection of event results / Or single result (if halted)
     */
    public function fireEvent($event, $params = [], $halt = FALSE)
    {
        if (!is_array($params)) $params = [$params];
        $result = [];

        // Single events
        if (isset($this->emitterSingleEvents[$event])) {
            foreach ($this->emitterSingleEvents[$event] as $callback) {
                $response = call_user_func_array($callback, $params);
                if (is_null($response)) continue;
                if ($halt) return $response;
                $result[] = $response;
            }

            unset($this->emitterSingleEvents[$event]);
        }

        // Recurring events, with priority
        if (isset($this->emitterEvents[$event])) {

            if (!isset($this->emitterEventSorted[$event]))
                $this->emitterEventSortEvents($event);

            foreach ($this->emitterEventSorted[$event] as $callback) {
                $response = call_user_func_array($callback, $params);
                if (is_null($response)) continue;
                if ($halt) return $response;
                $result[] = $response;
            }
        }

        return $halt ? null : $result;
    }

    /**
     * Fires a combination of local and global events. The first segment is removed
     * from the event name locally and the local object is passed as the first
     * argument to the event globally. Halting is also enabled by default.
     *
     * For example:
     *
     *     $this->fireSystemEvent('admin.form.myEvent', ['my value']);
     *
     * Is equivalent to:
     *
     *     $this->fireEvent('form.myEvent', ['myvalue'], true);
     *
     *     Event::fire('admin.form.myEvent', [$this, 'myvalue'], true);
     *
     * @param string $event Event name
     * @param array $params Event parameters
     * @param boolean $halt Halt after first non-null result
     *
     * @return mixed
     */
    public function fireSystemEvent($event, $params = [], $halt = TRUE)
    {
        $result = [];

        $shortEvent = substr($event, strpos($event, '.') + 1);

        $longArgs = array_merge([$this], $params);

        // Local event first
        if ($response = $this->fireEvent($shortEvent, $params, $halt)) {
            if ($halt)
                return $response;

            $result = array_merge($result, $response);
        }

        // Global event second
        if ($response = Event::fire($event, $longArgs, $halt)) {
            if ($halt)
                return $response;

            $result = array_merge($result, $response);
        }

        return $result;
    }
}
