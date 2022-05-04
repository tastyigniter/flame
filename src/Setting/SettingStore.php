<?php

namespace Igniter\Flame\Setting;

use Illuminate\Support\Arr;

abstract class SettingStore
{
    /**
     * The settings items.
     * @var array
     */
    protected $items = [];

    /**
     * Whether the store has changed since it was last loaded.
     * @var bool
     */
    protected $unsaved = false;

    /**
     * Whether the settings data are loaded.
     * @var bool
     */
    protected $loaded = false;

    /**
     * Get a specific key from the settings data.
     *
     * @param  string|array $key
     * @param  mixed $default Optional default value.
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $this->load();

        return Arr::get($this->items, $key, $default);
    }

    /**
     * Determine if a key exists in the settings data.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function has($key)
    {
        $this->load();

        return Arr::has($this->items, $key);
    }

    /**
     * Set a specific key to a value in the settings data.
     *
     * @param string|array $key Key string or associative array of key => value
     * @param mixed $value Optional only if the first argument is an array
     */
    public function set($key, $value = null)
    {
        $this->load();
        $this->unsaved = true;

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                Arr::set($this->items, $k, $v);
            }
        }
        else {
            Arr::set($this->items, $key, $value);
        }

        return $this;
    }

    /**
     * Unset a key in the settings data.
     *
     * @param  string $key
     */
    public function forget($key)
    {
        $this->unsaved = true;

        if ($this->has($key)) {
            Arr::forget($this->items, $key);
        }
    }

    /**
     * Unset all keys in the settings data.
     * @return void
     */
    public function forgetAll()
    {
        $this->unsaved = true;
        $this->items = [];
    }

    /**
     * Get all settings data.
     * @return array
     */
    public function all()
    {
        $this->load();

        return $this->items;
    }

    /**
     * Save any changes done to the settings data.
     * @return void
     */
    public function save()
    {
        if (!$this->unsaved) {
            // either nothing has been changed, or data has not been loaded, so
            // do nothing by returning early
            return;
        }

        $this->write($this->items);
        $this->unsaved = false;
    }

    /**
     * Make sure data is loaded.
     *
     * @param bool $force Force a reload of data. Default false.
     */
    public function load($force = false)
    {
        if (!$this->loaded || $force) {
            $this->items = $this->read();
            $this->loaded = true;
        }
    }

    /**
     * Read the data from the store.
     * @return array
     */
    abstract protected function read();

    /**
     * Write the data into the store.
     *
     * @param  array $data
     *
     * @return void
     */
    abstract protected function write(array $data);
}
