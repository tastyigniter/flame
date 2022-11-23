<?php

namespace Igniter\System\Helpers;

use Igniter\Flame\Igniter;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class CacheHelper
{
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
        self::clearCache();
        self::clearView();
        self::clearTemplates();

        self::clearCombiner();

        self::clearCompiled();
    }

    public static function clearView()
    {
        $path = config('view.compiled');
        foreach (File::glob("{$path}/*") as $view) {
            File::delete($view);
        }
    }

    public static function clearCombiner()
    {
        self::clearDirectory('/igniter/combiner');
    }

    public static function clearCache()
    {
        $path = config('igniter.system.parsedTemplateCachePath', '/igniter/cache');
        foreach (File::directories($path) as $directory) {
            File::deleteDirectory($directory);
        }
    }

    public static function clearTemplates()
    {
    }

    public static function clearCompiled()
    {
        File::delete(Igniter::getCachedAddonsPath());
        File::delete(App::getCachedPackagesPath());
        File::delete(App::getCachedServicesPath());
    }

    public static function clearDirectory($path)
    {
        if (!File::isDirectory(storage_path().$path))
            return;

        foreach (File::directories(storage_path().$path) as $directory) {
            File::deleteDirectory($directory);
        }
    }
}
