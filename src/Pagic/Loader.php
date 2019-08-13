<?php namespace Igniter\Flame\Pagic;

use App;
use Exception;
use File;
use Igniter\Flame\Pagic\Contracts\TemplateLoader;
use Igniter\Flame\Pagic\Contracts\TemplateSource;
use Illuminate\View\Compilers\CompilerInterface;

/**
 * Loader class
 */
class Loader implements TemplateLoader
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

    /**
     * @var \Main\Template\Model A object to load the template from.
     */
    protected $source;

    protected $compiler;

    /**
     * Sets a object to load the template from.
     *
     * @param \Igniter\Flame\Pagic\Contracts\TemplateSource $source Specifies the Template object.
     */
    public function setSource(TemplateSource $source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    /**
     * Gets the markup section of a template, given its name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The template source code
     *
     * @throws Exception When $name is not found
     */
    public function getMarkup($name)
    {
        return $this->getContents($name);
    }

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
        }
        catch (Exception $exception) {
            return FALSE;
        }
    }

    public function setCompiler(CompilerInterface $compiler)
    {
        $this->compiler = $compiler;
    }

    public function getCompiler()
    {
        if (is_null($this->compiler))
            $this->compiler = App::make('blade.compiler');

        return $this->compiler;
    }

    public function getFilePath()
    {
        return $this->getSource()->getFilePath();
    }
}
