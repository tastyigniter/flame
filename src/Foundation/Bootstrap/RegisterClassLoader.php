<?php namespace Igniter\Flame\Foundation\Bootstrap;

use Igniter\Flame\Support\ClassLoader;
use Igniter\Flame\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;

class RegisterClassLoader
{
    /**
     * Register The October Auto Loader
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
    }
}
