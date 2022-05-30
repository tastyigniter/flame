<?php

namespace Igniter\Flame\Filesystem;

use Illuminate\Filesystem\FilesystemServiceProvider as BaseFilesystemServiceProvider;

/**
 * Class FilesystemServiceProvider
 */
class FilesystemServiceProvider extends BaseFilesystemServiceProvider
{
    /**
     * Register the native filesystem implementation.
     * @return void
     */
    protected function registerNativeFilesystem()
    {
        $this->app->alias('files', Filesystem::class);

        $this->app->singleton('files', function () {
            $config = $this->app['config'];
            $files = new Filesystem;
            $files->filePermissions = $config->get('igniter.system.filePermissions', null);
            $files->folderPermissions = $config->get('igniter.system.folderPermissions', null);
            $files->pathSymbols = [
                '$' => public_path('vendor'),
                '~' => base_path(),
            ];

            return $files;
        });
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return ['files', 'filesystem'];
    }
}
