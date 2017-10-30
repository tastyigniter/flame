<?php

namespace Igniter\Flame\Flash;

use Illuminate\Session\Store;

class FlashStore
{
    /**
     * @var Store
     */
    protected $session;

    /**
     * Create a new session store instance.
     *
     * @param Store $session
     */
    public function __construct(Store $session)
    {
        $this->session = $session;
    }

    /**
     * Flash a message to the session.
     *
     * @param string $name
     * @param array $data
     */
    public function flash($name, $data)
    {
        $this->session->flash($name, $data);
    }

    public function get($key, $default = null)
    {
        return $this->session->get($key, $default);
    }

    public function forget($key)
    {
        $this->session->forget($key);
    }
}
