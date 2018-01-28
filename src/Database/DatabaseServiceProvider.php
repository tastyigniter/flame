<?php

namespace Igniter\Flame\Database;

use Illuminate\Database\DatabaseServiceProvider as BaseDatabaseServiceProvider;
use Illuminate\Support\Facades\Schema;

class DatabaseServiceProvider extends BaseDatabaseServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);

        Model::setEventDispatcher($this->app['events']);

        Schema::defaultStringLength(128);
    }

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        Model::clearBootedModels();

        Model::clearExtendedClasses();

        $this->registerEloquentFactory();

        $this->registerQueueableEntityResolver();

        $this->registerConnectionServices();
    }

    /**
     * Returns the default database driver, not just the connection name.
     * @return string
     */
    protected function getDefaultDatabaseDriver()
    {
        $defaultConnection = $this->app['db']->getDefaultConnection();

        return $this->app['config']['database.connections.'.$defaultConnection.'.driver'];
    }
}