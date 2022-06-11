<?php

namespace Igniter\Flame;

use Igniter\Admin\Models\User;
use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Main\Models\Customer;

class Igniter
{
    /**
     * The base path for extensions.
     *
     * @var string
     */
    protected static $extensionsPath;

    /**
     * The base path for themes.
     *
     * @var string
     */
    protected static $themesPath;

    /**
     * The base path for temporary directory.
     *
     * @var string
     */
    protected static $tempPath;

    /**
     * Indicates if the application has a valid database
     * connection and "settings" table.
     *
     * @var string
     */
    protected static $hasDatabase;

    protected static $coreMigrationPaths = [
        'igniter.system' => __DIR__.'/../System/Database/Migrations',
        'igniter.admin' => __DIR__.'/../Admin/Database/Migrations',
        'igniter.main' => __DIR__.'/../Main/Database/Migrations',
    ];

    protected static $migrationPaths = [];

    protected static $controllerPaths = [];

    /**
     * Set the extensions path for the application.
     *
     * @param string $path
     *
     * @return $this
     */
    public static function useExtensionsPath($path)
    {
        static::$extensionsPath = $path;
    }

    /**
     * Set the themes path for the application.
     *
     * @param string $path
     *
     * @return static
     */
    public static function useThemesPath($path)
    {
        static::$themesPath = $path;

        return new static;
    }

    /**
     * Set the temporary storage path for the application.
     *
     * @param string $path
     *
     * @return $this
     */
    public static function useTempPath($path)
    {
        static::$tempPath = $path;

        return new static;
    }

    /**
     * Determine if we are running in the admin area.
     *
     * @return bool
     */
    public static function runningInAdmin()
    {
        $requestPath = str_finish(normalize_uri(request()->path()), '/');
        $adminUri = str_finish(normalize_uri(static::uri()), '/');

        return starts_with($requestPath, $adminUri);
    }

    /**
     * Returns true if a database connection is present.
     * @return bool
     */
    public static function hasDatabase()
    {
        try {
            $hasDatabase = is_null(static::$hasDatabase)
                ? resolve('db.connection')->getSchemaBuilder()->hasTable('settings')
                : static::$hasDatabase;
        }
        catch (\Exception) {
            $hasDatabase = false;
        }

        return static::$hasDatabase = $hasDatabase;
    }

    /**
     * Get the path to the extensions directory.
     *
     * @return string
     */
    public static function extensionsPath()
    {
        return static::$extensionsPath ?: config('igniter.system.extensionsPath', base_path('extensions'));
    }

    /**
     * Get the path to the themes directory.
     *
     * @return string
     */
    public static function themesPath()
    {
        return static::$themesPath ?: config('igniter.system.themesPath', base_path('themes'));
    }

    /**
     * Get the path to the themes directory.
     *
     * @return string
     */
    public static function tempPath()
    {
        return static::$tempPath ?: config('igniter.system.tempPath', base_path('storage/temp'));
    }

    /**
     * Register database migration namespace.
     *
     * @param string $path
     * @param string $namespace
     * @return void
     */
    public static function loadMigrationsFrom(string $path, string $namespace)
    {
        static::$migrationPaths[$namespace] = $path;
    }

    /**
     * Get the database migration namespaces.
     *
     * @return array
     */
    public static function migrationPath()
    {
        return static::$migrationPaths;
    }

    public static function coreMigrationPath()
    {
        return static::$coreMigrationPaths;
    }

    public static function getSeedRecords($name)
    {
        return json_decode(file_get_contents(__DIR__.'/../../database/records/'.$name.'.json'), true);
    }

    /**
     * Get the path to the cached addons.php file.
     *
     * @return string
     */
    public static function getCachedAddonsPath()
    {
        return app()->bootstrapPath().'/cache/addons.php';
    }

    /**
     * Get the path to the cached classes.php file.
     *
     * @return string
     */
    public static function getCachedClassesPath()
    {
        return app()->bootstrapPath().'/cache/classes.php';
    }

    public static function loadResourcesFrom(string $path, string $namespace = null)
    {
        $callback = function ($files) use ($path, $namespace) {
            $files->pathSymbols[$namespace] = $path;
        };

        app()->afterResolving(Filesystem::class, $callback);

        if (app()->resolved(Filesystem::class)) {
            $callback(resolve(Filesystem::class), app());
        }
    }

    public static function loadControllersFrom(string $path, string $namespace)
    {
        static::$controllerPaths[$namespace] = $path;
    }

    public static function controllerPath()
    {
        return static::$controllerPaths;
    }

    public static function uri()
    {
        return config('igniter.routes.adminUri', '/admin');
    }

    public static function isUser($user)
    {
        return static::isAdminUser($user) || static::isCustomer($user);
    }

    public static function isCustomer($user)
    {
        return $user instanceof Customer;
    }

    public static function isAdminUser($user)
    {
        return $user instanceof User;
    }
}
