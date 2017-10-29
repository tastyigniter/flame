<?php

namespace Igniter\Flame\Flash;

class FlashBag
{
    const SESSION_KEY = '_flash_ti';

    /**
     * The session store.
     * @var SessionStore
     */
    protected $session;

    /**
     * The messages collection.
     *
     * @var \Illuminate\Support\Collection
     */
    public $messages;

    /**
     * Create a new FlashNotifier instance.
     *
     * @param array $messages
     * @param FlashStore $session
     */
    function __construct(array $messages = [], FlashStore $session)
    {
        $this->session = $session;
        $this->messages = collect();
    }

    /**
     * Get first message for every key in the bag.
     *
     * @param string|null $format
     *
     * @return array
     */
    public function all()
    {
        $messages = $this->session->get(static::SESSION_KEY, $this->messages);

        $this->clear();

        return $messages;
    }

    /**
     * Gets all the flash messages of a given type.
     *
     * @param string $key
     * @param string|null $format
     *
     * @return array
     */
    public function get($key, $format = null)
    {
        $message = parent::get($key, $format);

        $this->purge();

        return $message;
    }

    public function set($level = null, $message = null)
    {
        return $this->message($message, $level);
    }

    /**
     * Flash a generic message.
     *
     * @param  string|null $message
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
     * @param  string|null $message
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
     * @param  string|null $message
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
     * @param  string|null $message
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
     * @param  string|null $message
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
     * @param  string|null $message
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
     * @param  string|null $message
     * @param  string|null $level
     *
     * @return $this
     */
    public function message($message = null, $level = null)
    {
        // If no message was provided, we should update
        // the most recently added message.
        if (!$message) {
            return $this->updateLastMessage(compact('level'));
        }

        if (!$message instanceof Message) {
            $message = new Message(compact('message', 'level'));
        }

        $this->messages->push($message);

        return $this->flash();
    }

    /**
     * Modify the most recently added message.
     *
     * @param  array $overrides
     *
     * @return $this
     */
    protected function updateLastMessage($overrides = [])
    {
        $this->messages->last()->update($overrides);

        return $this;
    }

    /**
     * Flash an overlay modal.
     *
     * @param  string|null $message
     * @param  string $title
     *
     * @return $this
     */
    public function overlay($message = null, $title = 'Notice')
    {
        if (!$message) {
            return $this->updateLastMessage(['title' => $title, 'overlay' => TRUE]);
        }

        return $this->message(
            new OverlayMessage(compact('title', 'message'))
        );
    }

    /**
     * Add a "now" flash to the session.
     * @return $this
     */
    public function now()
    {
        return $this->updateLastMessage(['now' => TRUE]);
    }

    /**
     * Add an "important" flash to the session.
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
        $this->session->forget(static::SESSION_KEY);

        $this->messages = collect();

        return $this;
    }

    /**
     * Flash all messages to the session.
     */
    protected function flash()
    {
        $this->session->flash(static::SESSION_KEY, $this->messages);

        return $this;
    }
}

