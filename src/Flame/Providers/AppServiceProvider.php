<?php

namespace Igniter\Flame\Providers;

use Igniter\Flame\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

abstract class AppServiceProvider extends ServiceProvider
{
    protected $root = __DIR__.'/../../..';

    /**
     * Registers a new console (artisan) command
     *
     * @param string $key The command name
     * @param string $class The command class
     *
     * @return void
     */
    public function registerConsoleCommand($key, $class)
    {
        $key = 'command.'.$key;
        $this->app->singleton($key, $class);

        $this->commands($key);
    }

    public function loadAnonymousComponentFrom(string $directory, string $prefix = null)
    {
        $this->callAfterResolving(BladeCompiler::class, function ($blade) use ($directory, $prefix) {
            $blade->anonymousComponentNamespace($directory, $prefix);
        });
    }

    public function loadResourcesFrom(string $path, string $namespace = null)
    {
        $this->callAfterResolving(Filesystem::class, function ($files) use ($path, $namespace) {
            $files->pathSymbols[$namespace] = $path;
        });
    }
}
