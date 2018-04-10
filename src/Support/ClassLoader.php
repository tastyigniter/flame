<?php

namespace Igniter\Flame\Support;

use Exception;
use Igniter\Flame\Filesystem\Filesystem;

class ClassLoader
{
    /**
     * The filesystem instance.
     * @var \Igniter\Flame\Filesystem\Filesystem
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

    protected $manifestIsDirty = FALSE;

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

        $this->loadManifest();

        $this->registered = spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Store the manifest array by writing it to filesystem.
     *
     * @return void
     */
    public function store()
    {
        if (!$this->manifestIsDirty) {
            return;
        }

        $this->write($this->manifest);
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
        if (
            isset($this->manifest[$class]) &&
            $this->isRealFilePath($path = $this->manifest[$class])
        ) {
            require_once $this->basePath.DIRECTORY_SEPARATOR.$path;

            return TRUE;
        }

        // Try to load a mapped file for the prefix and relative class
        if ($mappedFile = $this->loadMappedClass($class)) {
            return $mappedFile;
        }

        // never found a mapped file
        return FALSE;
    }

    /**
     * Gets all the directories registered with the loader.
     *
     * @return array
     */
    public function getDirectories()
    {
        return $this->directories;
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
     * Get the normal file name for a class.
     *
     * @param  string $class
     *
     * @return array
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

        $lowerClass = strtolower($directory).DIRECTORY_SEPARATOR.$relativeClass;
        $upperClass = $directory.DIRECTORY_SEPARATOR.$relativeClass;

        return [$lowerClass, $upperClass];
    }

    /**
     * Load the mapped class for a directory prefix and relative class.
     *
     * @param string $class The class name.
     *
     * @return mixed bool false if no mapped file can be loaded, or the
     * name of the mapped file that was loaded.
     */
    protected function loadMappedClass($class)
    {
        list($lowerClass, $upperClass) = $this->normalizeClass($class);

        // Look through registered directories
        foreach ($this->directories as $directory) {

            // If the mapped class exists, require it
            if ($this->isRealFilePath($path = $directory.DIRECTORY_SEPARATOR.$lowerClass.'.php')) {
                $this->requireClass($class, $path);

                return $path;
            }

            if ($this->isRealFilePath($path = $directory.DIRECTORY_SEPARATOR.$upperClass.'.php')) {
                $this->requireClass($class, $path);

                return TRUE;
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
     * @param $class
     * @param string $path The file to require.
     *
     * @return void True if the file exists, false if not.
     */
    protected function requireClass($class, $path)
    {
        require_once $this->basePath.DIRECTORY_SEPARATOR.$path;

        $this->manifest[$class] = $path;

        $this->manifestIsDirty = TRUE;
    }

    /**
     * Load the manifest into memory.
     *
     * @return void
     */
    protected function loadManifest()
    {
        if (!is_null($this->manifest)) {
            return;
        }

        if (file_exists($this->manifestPath)) {
            try {
                $this->manifest = $this->files->getRequire($this->manifestPath);

                if (!is_array($this->manifest)) {
                    $this->manifest = [];
                }
            } catch (Exception $ex) {
                $this->manifest = [];
            }
        }
        else {
            $this->manifest = [];
        }
    }

    /**
     * Write the manifest array to filesystem.
     *
     * @param  array $manifest
     *
     * @return void
     * @throws \Exception
     */
    protected function write(array $manifest)
    {
        if (!is_writable($path = dirname($this->manifestPath))) {
            throw new Exception('The '.$path.' directory must be present and writable.');
        }

        $this->files->put(
            $this->manifestPath, '<?php return '.var_export($manifest, TRUE).';'
        );
    }
}