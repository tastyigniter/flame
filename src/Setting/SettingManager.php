<?php

namespace Igniter\Flame\Setting;

use Igniter\Flame\Foundation\Application;
use Illuminate\Support\Manager;

class SettingManager extends Manager
{
    /**
     * Get the default driver name.
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app->hasDatabase() ? 'config' : 'memory';
    }

    public function createConfigDriver()
    {
        $connectionName = $this->app['config']->get('database.default');
        $connection = $this->app['db']->connection($connectionName);

        $store = new DatabaseSettingStore($connection, 'settings', 'item', 'value');
        $store->setExtraColumns(['sort' => 'config']);

        return $store;
    }

    public function createPrefsDriver()
    {
        $connectionName = $this->app['config']->get('database.default');
        $connection = $this->app['db']->connection($connectionName);

        $store = new DatabaseSettingStore($connection, 'settings', 'item', 'value');
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