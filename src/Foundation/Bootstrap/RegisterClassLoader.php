<?php namespace Igniter\Flame\Foundation\Bootstrap;

use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Support\ClassLoader;
use Illuminate\Contracts\Foundation\Application;

class RegisterClassLoader
{
    /**
     * Register Auto Loader
     *
     * @param  \Illuminate\Contracts\Foundation\Application $app
     *
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $loader = new ClassLoader(
            new Filesystem,
            $app->basePath(),
            $app->getCachedClassesPath()
        );

        $app->instance(ClassLoader::class, $loader);

        $loader->register();

        $loader->addDirectories([
            'app',
            'extensions',
        ]);

        $app->after(function () use ($loader) {
            $loader->store();
        });
    }
}
