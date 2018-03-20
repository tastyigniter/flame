<?php namespace Igniter\Flame\Translation;

use Illuminate\Translation\FileLoader as FileLoaderBase;

class FileLoader extends FileLoaderBase
{
    /**
     * Translation driver instance.
     *
     * @var Contracts\Driver[]
     */
    protected $drivers = [];

    public function load($locale, $group, $namespace = null)
    {
        $lines = parent::load($locale, $group, $namespace);

        if (is_null($namespace) || $namespace == '*') {
            return $lines;
        }

        $driverLines = $this->loadFromDrivers($locale, $group, $namespace);

        return array_replace_recursive($lines, $driverLines);
    }

    /**
     * Get storage driver.
     *
     * @param $locale
     * @param $group
     * @param null $namespace
     *
     * @return Contracts\Driver
     */
    public function loadFromDrivers($locale, $group, $namespace = null)
    {
        return collect($this->drivers)->map(function ($className) {
            return app($className);
        })->mapWithKeys(function (Contracts\Driver $driver) use ($locale, $group, $namespace) {
            return $driver->load($locale, $group, $namespace);
        })->toArray();
    }

    public function addDriver($driver)
    {
        $this->drivers[] = $driver;
    }

    /**
     * Load a namespaced translation group.
     *
     * @param  string $locale
     * @param  string $group
     * @param  string $namespace
     *
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function loadNamespaced($locale, $group, $namespace)
    {
        if (isset($this->hints[$namespace])) {
            $lines = $this->loadPath($this->hints[$namespace], $locale, $group);

            return $this->loadNamespaceOverrides($lines, $locale, $group, $namespace);
        }

        return [];
    }

    /**
     * Load a local namespaced translation group for overrides.
     *
     * @param  array $lines
     * @param  string $locale
     * @param  string $group
     * @param  string $namespace
     *
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function loadNamespaceOverrides(array $lines, $locale, $group, $namespace)
    {
        $file = "{$this->path}/vendor/{$namespace}/{$locale}/{$group}.php";

        if ($this->files->exists($file)) {
            return array_replace_recursive($lines, $this->files->getRequire($file));
        }

        return $lines;
    }

    /**
     * Load a locale from a given path.
     *
     * @param  string $path
     * @param  string $locale
     * @param  string $group
     *
     * @return array
     */
    protected function loadPath($path, $locale, $group)
    {
        return collect(['_lang.php', '.php'])
            ->reduce(function ($output, $ext) use ($path, $locale, $group) {
                if ($this->files->exists($full = "{$path}/{$locale}/{$group}{$ext}")) {
                    $output = array_merge($output, $this->files->getRequire($full));
                }

                return $output;
            }, []);
    }
}