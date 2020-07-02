<?php

namespace Igniter\Flame\Flash;

class FlashBag
{
    protected $sessionKey = '_ti_flash';

    /**
     * The session store.
     * @var FlashStore
     */
    protected $store;

    /**
     * The messages collection.
     *
     * @var \Illuminate\Support\Collection
     */
    public $messages;

    /**
     * Create a new FlashNotifier instance.
     *
     * @param FlashStore $store
     */
    public function __construct(FlashStore $store)
    {
        $this->store = $store;
    }

    public function setSessionKey($key)
    {
        $this->sessionKey = $key;
    }

    public function messages()
    {
        if ($this->messages)
            return $this->messages;

        return $this->messages = $this->store->get($this->sessionKey, collect());
    }

    /**
     * Gets all the flash messages
     *
     * @return array
     */
    public function all()
    {
        $messages = $this->messages();

        $this->clear();

        return $messages;
    }

    public function set($level = null, $message = null)
    {
        return $this->message($message, $level);
    }

    /**
     * Flash a generic message.
     *
     * @param string|null $message
     *
     * @return $this
     */
    public function alert($message)
    {
        return $this->message($message);
    }

    /**
     * Flash an information message.
     *
     * @param string|null $message
     *
     * @return $this
     */
    public function info($message = null)
    {
        return $this->message($message, 'info');
    }

    /**
     * Flash a success message.
     *
     * @param string|null $message
     *
     * @return $this
     */
    public function success($message = null)
    {
        return $this->message($message, 'success');
    }

    /**
     * Flash an error message.
     *
     * @param string|null $message
     *
     * @return $this
     */
    public function error($message = null)
    {
        return $this->message($message, 'danger');
    }

    /**
     * Flash an error message.
     *
     * @param string|null $message
     *
     * @return $this
     */
    public function danger($message = null)
    {
        return $this->error($message);
    }

    /**
     * Flash a warning message.
     *
     * @param string|null $message
     *
     * @return $this
     */
    public function warning($message = null)
    {
        return $this->message($message, 'warning');
    }

    /**
     * Flash a general message.
     *
     * @param string|null $message
     * @param string|null $level
     *
     * @return $this
     */
    public function message($message = null, $level = null)
    {
        // If no message was provided, we should update
        // the most recently added message.
        if (is_null($message)) {
            return $this->updateLastMessage(compact('level'));
        }

        if (!$message instanceof Message) {
            $message = new Message(compact('message', 'level'));
        }

        $this->messages()->push($message);

        return $this->flash();
    }

    /**
     * Modify the most recently added message.
     *
     * @param array $overrides
     *
     * @return $this
     */
    protected function updateLastMessage($overrides = [])
    {
        $this->messages()->last()->update($overrides);

        return $this;
    }

    /**
     * Flash an overlay modal.
     *
     * @param string|null $message
     * @param string $title
     *
     * @return $this
     */
    public function overlay($message = null, $title = '')
    {
        if (!$message) {
            return $this->updateLastMessage(['title' => $title, 'overlay' => TRUE, 'important' => TRUE]);
        }

        return $this->message(
            new OverlayMessage(compact('title', 'message'))
        )->important();
    }

    /**
     * Add a "now" flash to the store.
     * @return $this
     */
    public function now()
    {
        return $this->updateLastMessage(['now' => TRUE]);
    }

    /**
     * Add an "important" flash to the store.
     * @return $this
     */
    public function important()
    {
        return $this->updateLastMessage(['important' => TRUE]);
    }

    /**
     * Clear all registered messages.
     * @return $this
     */
    public function clear()
    {
        $this->store->forget($this->sessionKey);

        $this->messages = collect();

        return $this;
    }

    /**
     * Flash all messages to the store.
     */
    protected function flash()
    {
        $this->store->flash($this->sessionKey, $this->messages());

        return $this;
    }
}

