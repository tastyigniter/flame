<?php

namespace Igniter\Flame\Scaffold;

use Illuminate\Support\ServiceProvider;

class ScaffoldServiceProvider extends ServiceProvider
{
    protected $defer = TRUE;

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'CreateExtension' => 'command.create.extension',
        'CreateComponent' => 'command.create.component',
        'CreateController' => 'command.create.controller',
        'CreateModel' => 'command.create.model',
        'CreateCommand' => 'command.create.command',
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands($this->commands);
    }

    /**
     * Register the given commands.
     *
     * @param  array $commands
     *
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach ($commands as $class => $command) {
            $this->{"register{$class}Command"}($command);
        }

        $this->commands(array_values($commands));
    }

    protected function registerCreateExtensionCommand($command)
    {
        $this->app->singleton($command, function ($app) {
            return new Console\CreateExtension($app['files']);
        });
    }

    protected function registerCreateComponentCommand($command)
    {
        $this->app->singleton($command, function ($app) {
            return new Console\CreateComponent($app['files']);
        });
    }

    protected function registerCreateControllerCommand($command)
    {
        $this->app->singleton($command, function ($app) {
            return new Console\CreateController($app['files']);
        });
    }

    protected function registerCreateModelCommand($command)
    {
        $this->app->singleton($command, function ($app) {
            return new Console\CreateModel($app['files']);
        });
    }

    protected function registerCreateCommandCommand($command)
    {
        $this->app->singleton($command, function ($app) {
            return new Console\CreateCommand($app['files']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_values($this->commands);
    }
}