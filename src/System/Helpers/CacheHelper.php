<?php

namespace Igniter\System\Helpers;

use Igniter\Flame\Igniter;
use Igniter\Flame\Support\Facades\File;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    use \Igniter\Flame\Traits\Singleton;

    /**
     * Execute the console command.
     */
    public static function clear()
    {
        Cache::flush();
        self::clearInternal();
    }

    public static function clearInternal()
    {
        $instance = self::instance();
        $instance->clearCache();
        $instance->clearView();
        $instance->clearTemplates();

        $instance->clearCombiner();

        $instance->clearMeta();
    }

    public function clearView()
    {
        $path = config('view.compiled');
        foreach (File::glob("{$path}/*") as $view) {
            File::delete($view);
        }
    }

    public function clearCombiner()
    {
        $this->clearDirectory('/igniter/combiner');
    }

    public function clearCache()
    {
        $path = config('igniter.system.parsedTemplateCachePath', '/igniter/cache');
        foreach (File::directories($path) as $directory) {
            File::deleteDirectory($directory);
        }
    }

    public function clearTemplates()
    {
    }

    public function clearCompiled()
    {
        File::delete(Igniter::getCachedAddonsPath());
        File::delete(App::getCachedPackagesPath());
        File::delete(App::getCachedServicesPath());
    }

    public function clearDirectory($path)
    {
        if (!File::isDirectory(storage_path().$path))
            return;

        foreach (File::directories(storage_path().$path) as $directory) {
            File::deleteDirectory($directory);
        }
    }
}
