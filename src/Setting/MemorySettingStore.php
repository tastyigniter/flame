<?php

namespace Igniter\Flame\Setting;

class MemorySettingStore extends SettingStore
{
    /**
     * @param array $items
     */
    public function __construct(array $items = null)
    {
        if ($items) {
            $this->items = $items;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function read()
    {
        return $this->items;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $items)
    {
        // do nothing
    }
}