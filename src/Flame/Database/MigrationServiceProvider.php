<?php

namespace Igniter\Flame\Database;

use Igniter\Flame\Database\Migrations\DatabaseMigrationRepository;
use Igniter\Flame\Database\Migrations\Migrator;
use Illuminate\Database\MigrationServiceProvider as BaseServiceProvider;

class MigrationServiceProvider extends BaseServiceProvider
{
    /**
     * Override the Laravel repository service.
     *
     * @return void
     */
    protected function registerRepository()
    {
        $this->app->singleton('migration.repository', function ($app) {
            $table = $app['config']['database.migrations'];

            return new DatabaseMigrationRepository($app['db'], $table);
        });
    }

    /**
     * Override the Laravel migrator singleton
     *
     * @return void
     */
    protected function registerMigrator()
    {
        $this->app->singleton('migrator', function ($app) {
            $repository = $app['migration.repository'];

            return new Migrator($repository, $app['db'], $app['files'], $app['events']);
        });
    }
}
