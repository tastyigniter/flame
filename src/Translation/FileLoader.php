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
     * @param $locale
     * @param $group
     * @param null $namespace
     *
     * @return array
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
        if (!$this->files->exists($this->path))
            return $lines;

        $namespace = str_replace('.', '/', $namespace);

        $file = "{$this->path}/{$locale}/{$namespace}/{$group}.php";
        if ($this->files->exists($file))
            return array_replace_recursive($lines, $this->files->getRequire($file));

        $file = "{$this->path}/bundles/{$locale}/{$namespace}/{$group}.php";
        if ($this->files->exists($file))
            return array_replace_recursive($lines, $this->files->getRequire($file));

        return $lines;
    }
}