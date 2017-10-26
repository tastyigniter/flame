<?php namespace Igniter\Flame\Pagic;

use App;
use Exception;
use File;

/**
 * Loader class
 */
class Loader
{
    /**
     * @var string Expected file extension
     */
    protected $extension = 'php';

    /**
     * @var array Cache
     */
    protected $fallbackCache = [];

    /**
     * @var array Cache
     */
    protected $cache = [];

    public function getContents($name)
    {
        return File::get($this->findTemplate($name));
    }

    public function getFilename($name)
    {
        return $this->findTemplate($name);
    }

    /**
     * Gets the path of a view file
     *
     * @param  string $name
     *
     * @return string
     */
    protected function findTemplate($name)
    {
        $finder = App::make('view')->getFinder();

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        if (File::isFile($name)) {
            return $this->cache[$name] = $name;
        }

        $view = $name;
        if (File::extension($view) == $this->extension) {
            $view = substr($view, 0, -strlen($this->extension));
        }

        $path = $finder->find($view);

        return $this->cache[$name] = $path;
    }

    public function getCacheKey($name)
    {
        return $this->findTemplate($name);
    }

    public function isFresh($name, $time)
    {
        return File::lastModified($this->findTemplate($name)) <= $time;
    }

    public function exists($name)
    {
        try {
            $this->findTemplate($name);

            return TRUE;
        } catch (Exception $exception) {
            return FALSE;
        }
    }
}
