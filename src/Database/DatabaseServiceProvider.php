<?php

namespace Igniter\Flame\Database;

use Igniter\Flame\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\DatabaseServiceProvider as BaseDatabaseServiceProvider;
use Illuminate\Database\DatabaseTransactionsManager;
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
     * Register the primary database bindings.
     *
     * @return void
     */
    protected function registerConnectionServices()
    {
        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });

        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });

        $this->app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });

        $this->app->singleton('db.transactions', function ($app) {
            return new DatabaseTransactionsManager;
        });
    }
}
