<?php

namespace Igniter\Flame\Setting;

use Illuminate\Support\Manager;

class SettingManager extends Manager
{
    /**
     * Get the default driver name.
     * @return string
     */
    public function getDefaultDriver()
    {
        return 'config';
    }

    public function createConfigDriver()
    {
        $store = new DatabaseSettingStore($this->app['db'], $this->app['cache.store']);
        $store->setCacheKey('igniter.setting.system');
        $store->setExtraColumns(['sort' => 'config']);

        return $store;
    }

    public function createPrefsDriver()
    {
        $store = new DatabaseSettingStore($this->app['db'], $this->app['cache.store']);
        $store->setCacheKey('igniter.setting.parameters');
        $store->setExtraColumns(['sort' => 'prefs']);

        return $store;
    }

    public function createMemoryDriver()
    {
        return new MemorySettingStore();
    }

    public function createArrayDriver()
    {
        return $this->createMemoryDriver();
    }
}