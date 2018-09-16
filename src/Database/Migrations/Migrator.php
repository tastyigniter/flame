<?php

namespace Igniter\Flame\Database\Migrations;

use Illuminate\Database\Migrations\Migrator as BaseMigrator;
use Str;

class Migrator extends BaseMigrator
{
    public function run($paths = [], array $options = [])
    {
        foreach ($paths as $group => $path) {
            $this->getRepository()->setGroup($group);
            parent::run($path, $options);
        }
    }

    public function rollbackAll($paths = [], array $options = [])
    {
        $this->notes = [];

        foreach ($paths as $group => $path) {
            $this->getRepository()->setGroup($group);

            $this->rollDown($path, $options);
        }
    }

    public function rollDown($paths = [], array $options = [])
    {
        $this->requireFiles(
            $migrations = $this->getMigrationFiles($paths)
        );

        if (count($migrations) === 0) {
            $this->note('<info>Nothing to rollback.</info>');

            return;
        }

        foreach ($migrations as $migration => $file) {
            $this->runDown($file, $migration, $options['pretend'] ?? FALSE);
        }

        return $this;
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param  string $file
     *
     * @return object
     */
    public function resolve($file)
    {
        $class = Str::studly(implode('_', array_slice(explode('_', $file), 4)));
        if (!class_exists($class)) {
            $className = str_replace('.', '\\', $this->getRepository()->getGroup());
            $class = $className.'\\Database\\Migrations\\'.$class;
        }

        return new $class;
    }
}