<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Igniter;
use Igniter\System\Models\Extension;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use ZipArchive;

/**
 * Modules class for TastyIgniter.
 * Provides utility functions for working with modules.
 */
class ExtensionManager
{
    /**
     * @var \Igniter\System\Classes\PackageManifest
     */
    protected $packageManifest;

    /**
     * @var array used for storing extension information objects.
     */
    protected $extensions = [];

    /**
     * @var array of installed extensions.
     */
    protected $installedExtensions = [];

    /**
     * @var array Cache of registration method results.
     */
    protected $registrationMethodCache = [];

    /**
     * @var array of extensions and their directory paths.
     */
    protected $paths = [];

    /**
     * @var array used Set whether extensions have been booted.
     */
    protected $booted = false;

    /**
     * @var array used Set whether extensions have been registered.
     */
    protected $registered = false;

    protected static $directories = [];

    public function __construct(PackageManifest $packageManifest)
    {
        $this->packageManifest = $packageManifest;
        $this->loadInstalled();
        $this->loadExtensions();
        $this->loadDependencies();
    }

    public static function addDirectory($directory)
    {
        self::$directories[] = $directory;
    }

    /**
     * Return the path to the extension and its specified folder.
     *
     * @param $extension string The name of the extension (must match the folder name).
     * @param $folder string The folder name to search for (Optional).
     *
     * @return string The path, relative to the front controller.
     */
    public function path($extension, $folder = null)
    {
        $path = array_get($this->paths(), $extension);

        if (!is_null($folder))
            return $path.'/'.$folder;

        return $path.'/';
    }

    /**
     * Return an associative array of files within one or more extensions.
     *
     * @param string $extensionName
     * @param string $subFolder
     *
     * @return bool|array An associative array, like:
     * <code>
     * array(
     *     'extension_name' => array(
     *         'folder' => array('file1', 'file2')
     *     )
     * )
     */
    public function files($extensionName = null, $subFolder = null)
    {
        $files = [];
        traceLog('Deprecated method');

        return $files;
    }

    /**
     * Search a extension folder for files.
     *
     * @param $extensionName   string  If not null, will return only files from that extension.
     * @param $path string  If not null, will return only files within
     * that sub-folder of each extension (ie 'views').
     *
     * @return array
     */
    public function filesPath($extensionName, $path = null)
    {
        traceLog('Deprecated method');

        return [];
    }

    /**
     * Returns an array of the folders in which extensions may be stored.
     * @return array The folders in which extensions may be stored.
     */
    public function folders()
    {
        $paths = [];

        $directories = self::$directories;
        if (File::isDirectory($extensionsPath = Igniter::extensionsPath()))
            array_unshift($directories, $extensionsPath);

        foreach ($directories as $directory) {
            foreach (File::glob($directory.'/*/*/Extension.php') as $path) {
                $paths[] = dirname($path);
            }
        }

        return $paths;
    }

    /**
     * Returns a list of all extensions in the system.
     * @return array A list of all extensions in the system.
     */
    public function listExtensions()
    {
        return array_keys($this->paths());
    }

    /**
     * Scans extensions to locate any dependencies that are not currently
     * installed. Returns an array of extension codes that are needed.
     * @return array
     */
    public function findMissingDependencies()
    {
        $result = $missing = [];
        foreach ($this->extensions as $code => $extension) {
            if (!$required = $this->getDependencies($extension))
                continue;

            foreach ($required as $require) {
                if ($this->hasExtension($require))
                    continue;

                if (!in_array($require, $missing)) {
                    $missing[] = $require;
                    $result[$code][] = $require;
                }
            }
        }

        return $result;
    }

    /**
     * Checks all extensions and their dependencies, if not met extensions
     * are disabled.
     * @return void
     */
    protected function loadDependencies()
    {
        foreach ($this->extensions as $code => $extension) {
            if (!$required = $this->getDependencies($extension))
                continue;

            $disable = false;
            foreach ($required as $require) {
                $extensionObj = $this->findExtension($require);
                if (!$extensionObj || $extensionObj->disabled)
                    $disable = true;
            }

            // Only disable extension with missing dependencies.
            if ($disable && !$extension->disabled)
                $this->updateInstalledExtensions($code, false);
        }
    }

    /**
     * Returns the extension codes that are required by the supplied extension.
     *
     * @param string $extension
     *
     * @return bool|array
     */
    public function getDependencies($extension)
    {
        if (is_string($extension) && (!$extension = $this->findExtension($extension)))
            return false;

        return array_keys($extension->listRequires());
    }

    /**
     * Sorts extensions, in the order that they should be actioned,
     * according to their given dependencies. Least required come first.
     *
     * @param array $extensions Array to sort, or null to sort all.
     *
     * @return array Collection of sorted extension identifiers
     */
    public function listByDependencies($extensions = null)
    {
        if (!is_array($extensions))
            $extensions = $this->getExtensions();

        $result = [];
        $checklist = $extensions;

        $loopCount = 0;
        while (count($checklist) > 0) {
            if (++$loopCount > 999) {
                throw new ApplicationException('Too much recursion');
            }

            foreach ($checklist as $code => $extension) {
                $depends = $this->getDependencies($extension) ?: [];
                $depends = array_filter($depends, function ($dependCode) use ($extensions) {
                    return isset($extensions[$dependCode]);
                });

                $depends = array_diff($depends, $result);
                if (count($depends) > 0)
                    continue;

                $result[] = $code;
                unset($checklist[$code]);
            }
        }

        return $result;
    }

    /**
     * Create a Directory Map of all extensions
     * @return array A list of all extensions in the system.
     */
    public function paths()
    {
        return $this->paths;
    }

    /**
     * Finds all available extensions and loads them in to the $extensions array.
     * @return array
     * @throws \Igniter\Flame\Exception\SystemException
     */
    public function loadExtensions()
    {
        $this->extensions = [];
        $this->packageManifest->manifest = null;

        foreach ($this->packageManifest->extensions() as $code => $config) {
            $this->loadExtensionFromConfig($code, $config);
        }

        foreach ($this->folders() as $path) {
            $this->loadExtension($path);
        }

        return $this->extensions;
    }

    public function loadExtension($path)
    {
        $vendor = basename(dirname($path));
        $namespace = ucfirst($vendor).'\\'.ucfirst(basename($path)).'\\';
        $class = $namespace.'Extension';

        $identifier = $this->getIdentifier($namespace);

        if (isset($this->extensions[$identifier])) {
            return $this->extensions[$identifier];
        }

        if (!class_exists($class)) {
            throw new SystemException("Missing Extension class '{$class}' in '{$identifier}', create the Extension class to override extensionMeta() method.");
        }

        $classObj = new $class(app());

        // Check for disabled extensions
        if ($this->isDisabled($identifier)) {
            $classObj->disabled = true;
        }

        $this->extensions[$identifier] = $classObj;
        $this->paths[$identifier] = $path;

        return $classObj;
    }

    /**
     * Loads a single extension in to the manager.
     *
     * @param string $code Eg: extension code
     * @param string $path Eg: base_path().'/extensions/vendor/extension';
     *
     * @return object|bool
     * @throws \Igniter\Flame\Exception\SystemException
     */
    public function loadExtensionFromConfig($code, $config)
    {
        if (!$this->checkName($code)) return false;

        $identifier = $this->getIdentifier($code);

        if (isset($this->extensions[$identifier])) {
            return $this->extensions[$identifier];
        }

        $path = array_get($config, 'directory');
        if (!$path || !File::isDirectory($path))
            return false;

        if (!($class = array_get($config, 'extensionClass')) || !class_exists($class))
            return false;

        $classObj = new $class(app());

        // Check for disabled extensions
        if ($this->isDisabled($identifier)) {
            $classObj->disabled = true;
        }

        $this->extensions[$identifier] = $classObj;
        $this->paths[$identifier] = $path;

        return $classObj;
    }

    /**
     * Runs the boot() method on all extensions. Can only be called once.
     * @return void
     */
    public function bootExtensions()
    {
        if ($this->booted) {
            return;
        }

        foreach (array_keys($this->installedExtensions) as $code) {
            $this->bootExtension($this->findExtension($code));
        }

        $this->booted = true;
    }

    /**
     * Boot a single extension.
     *
     * @param \Igniter\System\Classes\BaseExtension $extension
     *
     * @return void
     */
    public function bootExtension($extension = null)
    {
        if (!$extension) {
            return;
        }

        if ($extension->disabled) {
            return;
        }

        $extension->boot();
    }

    /**
     * Runs the register() method on all extensions. Can only be called once.
     * @return void
     */
    public function registerExtensions()
    {
        if ($this->registered) {
            return;
        }

        foreach ($this->extensions as $code => $extension) {
            $this->registerExtension($code, $extension);
        }

        $this->registered = true;
    }

    /**
     * Register a single extension.
     *
     * @param \Igniter\System\Classes\BaseExtension $extension
     *
     * @return void
     */
    public function registerExtension($code, $extension = null)
    {
        if (!$extension) {
            return;
        }

        $extensionPath = $this->getExtensionPath($code);
        $extensionCode = strtolower($code);
        $extensionNamespace = array_get($extension->extensionMeta(), 'namespace');

        // Register resources path symbol
        if (File::isDirectory($resourcesPath = $extensionPath.'/resources') ||
            File::isDirectory($resourcesPath = $extensionPath)) {
            Igniter::loadResourcesFrom($resourcesPath, $extensionCode);
        }

        if (File::isDirectory($langPath = $extensionPath.'/resources/lang') ||
            File::isDirectory($langPath = $extensionPath.'/language')) {
            Lang::addNamespace($extensionCode, $langPath);
        }

        // Register migrations path
        if (File::isDirectory($migrationPath = $extensionPath.'/src/Database/Migrations') ||
            File::isDirectory($migrationPath = $extensionPath.'/database/migrations')) {
            Igniter::loadMigrationsFrom($migrationPath, $extensionCode);
        }

        if ($extension->disabled) {
            return;
        }

        $extension->register();

        // Register controller path
        if (File::isDirectory($controllerPath = $extensionPath.'/controllers')) {
            Igniter::loadControllersFrom($controllerPath, $extensionNamespace.'Controllers');
        }

        // Register controller path
        if (File::isDirectory($controllerPath = $extensionPath.'/src/Http/Controllers')) {
            Igniter::loadControllersFrom($controllerPath, $extensionNamespace.'Http\\Controllers');
        }

        if (File::isDirectory($controllerPath = $extensionPath.'/src/Controllers')) {
            Igniter::loadControllersFrom($controllerPath, $extensionNamespace.'Controllers');
        }

        // Register views path
        if (File::isDirectory($viewsPath = $extensionPath.'/views') ||
            File::isDirectory($viewsPath = $extensionPath.'/resources/views')) {
            View::addNamespace($extensionCode, $viewsPath);
        }

        // Add routes, if available
        if (File::exists($routesFile = $extensionPath.'/routes.php') ||
            File::exists($routesFile = $extensionPath.'/routes/routes.php')) {
            require $routesFile;
        }
    }

    /**
     * Returns an array with all registered extensions
     * The index is the extension name, the value is the extension object.
     *
     * @return BaseExtension[]
     */
    public function getExtensions()
    {
        $extensions = [];
        foreach ($this->extensions as $code => $extension) {
            if (!$extension->disabled)
                $extensions[$code] = $extension;
        }

        return $extensions;
    }

    /**
     * Returns a extension registration class based on its name.
     *
     * @param $code
     *
     * @return \Igniter\System\Classes\BaseExtension|null
     */
    public function findExtension($code)
    {
        if (!$this->hasExtension($code)) {
            return null;
        }

        return $this->extensions[$code];
    }

    /**
     * Checks to see if an extension name is well formed.
     *
     * @param $code
     *
     * @return string
     */
    public function checkName($code)
    {
        return (strpos($code, '_') === 0 || preg_match('/\s/', $code)) ? null : $code;
    }

    public function getIdentifier($namespace)
    {
        $namespace = trim($namespace, '\\');

        return strtolower(str_replace('\\', '.', $namespace));
    }

    public function getNamePath($code)
    {
        return str_replace('.', '/', $code);
    }

    public function getExtensionPath($code, $path = '')
    {
        return ($this->paths[$code] ?? null).$path;
    }

    /**
     * Checks to see if an extension has been registered.
     *
     * @param $code
     *
     * @return bool
     */
    public function hasExtension($code)
    {
        return isset($this->extensions[$code]);
    }

    public function hasVendor($path)
    {
        return array_key_exists($path, array_undot($this->paths()));
    }

    /**
     * Returns a flat array of extensions namespaces and their paths
     */
    public function namespaces()
    {
        $classNames = [];

        foreach ($this->packageManifest->extensions() as $extension) {
            $namespace = normalize_class_name(array_get($extension, 'namespace'));
            $classNames[$namespace] = array_get($extension, 'path');
        }

        return $classNames;
    }

    /**
     * Determines if an extension is disabled by looking at the installed extensions config.
     *
     * @param $code
     *
     * @return bool
     */
    public function isDisabled($code)
    {
        return !$this->checkName($code) || !array_get($this->installedExtensions, $code, false);
    }

    /**
     * Spins over every extension object and collects the results of a method call.
     * @param string $methodName
     * @return array
     */
    public function getRegistrationMethodValues($methodName)
    {
        if (isset($this->registrationMethodCache[$methodName])) {
            return $this->registrationMethodCache[$methodName];
        }

        $results = [];
        $extensions = $this->getExtensions();
        foreach ($extensions as $id => $extension) {
            if (!is_callable([$extension, $methodName])) {
                continue;
            }

            $results[$id] = $extension->{$methodName}();
        }

        return $this->registrationMethodCache[$methodName] = $results;
    }

    /**
     * Loads all installed extension from application config.
     */
    public function loadInstalled()
    {
        $this->installedExtensions = $this->packageManifest->installExtensions();
    }

    /**
     * @param string $code
     * @param bool $enable
     * @return bool
     */
    public function updateInstalledExtensions($code, $enable = true)
    {
        $code = $this->getIdentifier($code);

        if (is_null($enable)) {
            array_pull($this->installedExtensions, $code);
        }
        else {
            $this->installedExtensions[$code] = $enable;
        }

        $this->packageManifest->installExtensions($this->installedExtensions);

        if ($extension = $this->findExtension($code)) {
            $extension->disabled = $enable === false;
        }

        return true;
    }

    /**
     * Delete extension the filesystem
     *
     * @param array $extCode The extension to delete
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public function removeExtension($extCode = null)
    {
        if (!$extensionPath = $this->getExtensionPath($extCode))
            return false;

        // Delete the specified extension folder.
        if (File::isDirectory($extensionPath))
            File::deleteDirectory($extensionPath);

        $vendorPath = dirname($extensionPath);

        // Delete the specified extension vendor folder if it has no extension.
        if (File::isDirectory($vendorPath) &&
            !count(File::directories($vendorPath))
        )
            File::deleteDirectory($vendorPath);

        return true;
    }

    /**
     * Extract uploaded extension zip folder
     *
     * @param $zipPath
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public function extractExtension($zipPath)
    {
        $extensionCode = null;
        $extractTo = Igniter::extensionsPath();

        $zip = new ZipArchive;
        if ($zip->open($zipPath) === true) {
            $extensionDir = $zip->getNameIndex(0);

            if (!$this->checkName($extensionDir))
                throw new SystemException('Extension name can not have spaces.');

            if ($zip->locateName($extensionDir.'Extension.php') === false)
                throw new SystemException('Extension registration class was not found.');

            $meta = @json_decode($zip->getFromName($extensionDir.'extension.json'));
            if (!$meta || !strlen($meta->code))
                throw new SystemException(lang('igniter::system.extensions.error_config_no_found'));

            $extensionCode = $meta->code;
            $extractToPath = $extractTo.'/'.$this->getNamePath($meta->code);
            $zip->extractTo($extractToPath);
            $zip->close();
        }

        return $extensionCode;
    }

    /**
     * Install a new or existing extension by code
     *
     * @param string $code
     * @param string $version
     * @return bool
     */
    public function installExtension($code, $version = null)
    {
        $model = Extension::firstOrNew(['name' => $code]);
        if (!$model->applyExtensionClass())
            return false;

        if (!$extension = $this->findExtension($model->name))
            return false;

        // Register and boot the extension to make
        // its services available before migrating
        $extension->disabled = false;
        $this->registerExtension($model->name, $extension);
        $this->bootExtension($extension);

        // set extension migration to the latest version
        resolve(UpdateManager::class)->migrateExtension($model->name);

        $model->version = $version ?? $this->packageManifest->getVersion($model->name) ?? $model->version;
        $model->save();

        $this->updateInstalledExtensions($model->name);

        return true;
    }

    /**
     * Uninstall a new or existing extension by code
     *
     * @param string $code
     *
     * @param bool $purgeData
     * @return bool
     */
    public function uninstallExtension($code, $purgeData = false)
    {
        if ($purgeData)
            resolve(UpdateManager::class)->purgeExtension($code);

        $this->updateInstalledExtensions($code, false);

        return true;
    }

    /**
     * Delete a single extension by code
     *
     * @param string $code
     * @param bool $purgeData
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteExtension($code, $purgeData = true)
    {
        if ($extensionModel = Extension::where('name', $code)->first())
            $extensionModel->delete();

        if ($purgeData)
            resolve(UpdateManager::class)->purgeExtension($code);

        // Remove extensions files from filesystem
        $this->removeExtension($code);

        // remove extension from installed.json meta file
        $this->updateInstalledExtensions($code, null);

        return true;
    }
}
