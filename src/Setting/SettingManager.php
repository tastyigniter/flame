<?php

namespace Igniter\Flame\Setting;

use Igniter\Flame\Foundation\Application;
use Illuminate\Support\Manager;

class SettingManager extends Manager
{
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get the default driver name.
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app->hasDatabase() ? 'database' : 'memory';
    }

    public function createDatabaseDriver()
    {
        $connectionName = $this->app['config']->get('database.default');
        $connection = $this->app['db']->connection($connectionName);

        return new DatabaseSettingStore($connection, 'settings', 'item', 'value');
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