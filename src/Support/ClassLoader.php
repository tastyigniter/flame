<?php

namespace Igniter\Flame\Support;

use Igniter\Flame\Filesystem\Filesystem;

class ClassLoader
{
    /**
     * The filesystem instance.
     * @var \October\Rain\Filesystem\Filesystem
     */
    public $files;

    /**
     * The base path.
     * @var string
     */
    public $basePath;

    /**
     * The manifest path.
     * @var string|null
     */
    public $manifestPath;

    /**
     * The loaded manifest array.
     * @var array
     */
    public $manifest;

    /**
     * The registered directories.
     * @var array
     */
    protected $directories = [];

    /**
     * Indicates if a loader has been registered.
     * @var bool
     */
    protected $registered = FALSE;

    public function __construct(Filesystem $files, $basePath, $manifestPath)
    {
        $this->files = $files;
        $this->basePath = $basePath;
        $this->manifestPath = $manifestPath;
    }

    /**
     * Register loader with SPL autoloader stack.
     * @return void
     */
    public function register()
    {
        if ($this->registered) {
            return;
        }

        $this->registered = spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Loads the class file for a given class name.
     *
     * @param string $class The fully-qualified class name.
     *
     * @return mixed The mapped file name on success, or boolean false on
     * failure.
     */
    public function loadClass($class)
    {
        $classPath = $this->normalizeClass($class);

        // Try to load a mapped file for the prefix and relative class
        if ($mappedFile = $this->loadMappedClass($class, $classPath)) {
            return $mappedFile;
        }

        // never found a mapped file
        return FALSE;
    }

    /**
     * Get the normal file name for a class.
     *
     * @param  string $class
     *
     * @return string
     */
    protected function normalizeClass($class)
    {
        // Strip first slash
        $class = ltrim($class, '\\');

        // Work backwards through the namespace names of the fully-qualified
        // class name to find a mapped file name
        $pos = strrpos($class, '\\');

        // Retain the trailing namespace separator in the prefix
        $directory = substr($class, 0, $pos + 1);

        // The rest is the relative class name
        $relativeClass = substr($class, $pos + 1);
        $directory = str_replace(['\\', '.'], DIRECTORY_SEPARATOR, $directory);

        $directory = trim($directory, DIRECTORY_SEPARATOR);

        return $directory.DIRECTORY_SEPARATOR.$relativeClass;
    }

    /**
     * Load the mapped class for a directory prefix and relative class.
     *
     * @param string $class The class name.
     * @param string $classPath The class relative path.
     *
     * @return mixed bool false if no mapped file can be loaded, or the
     * name of the mapped file that was loaded.
     */
    protected function loadMappedClass($class, $classPath)
    {
        // Look through registered directories
        foreach ($this->directories as $directory) {

            // If the mapped class exists, require it
            if ($this->isRealFilePath($path = $directory.DIRECTORY_SEPARATOR.$classPath.'.php')) {
                $this->requireClass($class, $path);

                return $path;
            }
        }

        // never found it
        return FALSE;
    }

    /**
     * Determine if a relative path to a file exists and is real
     *
     * @param  string $path
     *
     * @return bool
     */
    protected function isRealFilePath($path)
    {
        return is_file(realpath($this->basePath.DIRECTORY_SEPARATOR.$path));
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param string $path The file to require.
     *
     * @return bool True if the file exists, false if not.
     */
    protected function requireClass($class, $path)
    {
        require_once $path;

        $this->manifest[$class] = $path;
    }

    /**
     * Add directories to the class loader.
     *
     * @param  string|array $directories
     *
     * @return void
     */
    public function addDirectories($directories)
    {
        $this->directories = array_merge($this->directories, (array)$directories);

        $this->directories = array_unique($this->directories);
    }

    /**
     * Remove directories from the class loader.
     *
     * @param  string|array $directories
     *
     * @return void
     */
    public function removeDirectories($directories = null)
    {
        if (is_null($directories)) {
            $this->directories = [];
        }
        else {
            $directories = (array)$directories;

            $this->directories = array_filter($this->directories, function ($directory) use ($directories) {
                return !in_array($directory, $directories);
            });
        }
    }

    /**
     * Gets all the directories registered with the loader.
     * @return array
     */
    public function getDirectories()
    {
        return $this->directories;
    }
}