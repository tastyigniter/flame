<?php namespace Igniter\Flame\Translation;

use Illuminate\Translation\FileLoader as FileLoaderBase;

class FileLoader extends FileLoaderBase
{
    /**
     * Load a namespaced translation group.
     *
     * @param  string $locale
     * @param  string $group
     * @param  string $namespace
     *
     * @return array
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
     */
    protected function loadNamespaceOverrides(array $lines, $locale, $group, $namespace)
    {
//        $namespace = str_replace('.', '/', $namespace);
//        $file = "{$this->path}/{$locale}/{$namespace}/{$group}.php";
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
        $foundPath = null;
        if ($this->files->exists($full = "{$path}/{$locale}/{$group}_lang.php")) {
            $foundPath = $full;
        }
        else if ($this->files->exists($full = "{$path}/{$locale}/{$group}.php")) {
            $foundPath = $full;
        }

        if (!$foundPath)
            return [];

        return $this->files->getRequire($foundPath);
    }
}