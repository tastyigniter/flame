<?php

namespace Igniter\Flame\Pagic;

use Igniter\Flame\Pagic\Extension\AbstractExtension;
use Igniter\Flame\Pagic\Extension\CoreExtension;
use Igniter\Flame\Support\Facades\File;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Illuminate\View\Compilers\CompilerInterface;
use LogicException;

class Environment
{
    protected $extensionInitialized = false;

    protected $loadedTemplates;

    public $loader;

    protected $cache;

    /**
     * @var CompilerInterface
     */
    protected $compiler;

    /**
     * @var array Cache for global variables.
     */
    protected static $globalsCache;

    protected $templateClassPrefix = '__PagicTemplate_';

    protected $extensions = [];

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var mixed
     */
    protected $templateClass;

    protected $charset;

    /**
     * Constructor.
     * Available options:
     *  * debug: When set to true, it automatically set "auto_reload" to true as
     *           well (default to false).
     *  * charset: The charset used by the templates (default to UTF-8).
     *  * templateClass: The base template class to use for generated
     *                         templates.
     *  * cache: An absolute path where to store the compiled templates,
     *           or false to disable compilation cache.
     *
     * @param Contracts\TemplateLoader $loader
     * @param array $options An array of options
     */
    public function __construct(Contracts\TemplateLoader $loader, $options = [])
    {
        $this->setLoader($loader);

        $options = array_merge([
            'debug' => false,
            'charset' => 'UTF-8',
            'templateClass' => \Igniter\Flame\Pagic\Template::class,
            'cache' => null,
        ], $options);

        $this->debug = (bool)$options['debug'];
        $this->templateClass = $options['templateClass'];
        $this->setCharset($options['charset']);
        $this->setCache($options['cache']);

        $this->addExtension(new CoreExtension());

        View::share('___env', $this);
    }

    public function setLoader(Contracts\TemplateLoader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Gets the Loader instance.
     * @return Loader
     */
    public function getLoader()
    {
        if (null === $this->loader) {
            throw new LogicException('You must set a loader first.');
        }

        return $this->loader;
    }

    /**
     * Sets the default template charset.
     *
     * @param string $charset The default charset
     */
    public function setCharset($charset)
    {
        $this->charset = strtoupper($charset);
    }

    /**
     * Gets the default template charset.
     * @return string The default charset
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Gets the current cache implementation.
     *
     * @return \Igniter\Flame\Pagic\Cache\FileSystem
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Sets the current cache implementation.
     *
     * @param \Igniter\Flame\Pagic\Cache\FileSystem
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    public function getCompiler()
    {
        if (is_null($this->compiler))
            $this->compiler = App::make('blade.compiler');

        $this->initExtensions();

        return $this->compiler;
    }

    public function setCompiler(CompilerInterface $compiler)
    {
        $this->compiler = $compiler;
    }

    public function getTemplateClass()
    {
        return $this->templateClass;
    }

    /**
     * Renders a template.
     *
     * @param string $name The template name
     * @param array $context An array of parameters to pass to the template
     *
     * @return string The rendered template
     * @throws \Exception
     * @throws \Throwable
     */
    public function render($name, array $context = [])
    {
        return $this->load($name)->render($context);
    }

    /**
     * Loads a template.
     *
     * @param string|Template $name The template name
     *
     * @return Template
     * @throws \Exception
     */
    public function load($name)
    {
        return $this->loadTemplate($name, $this->getCache()->getCacheKey($name, true));
    }

    /**
     * Loads a template internal representation.
     *
     * @param string $name The template path
     * @param string $path The template cache path
     *
     * @return Template
     */
    public function loadTemplate($name, $path)
    {
        if (isset($this->loadedTemplates[$name])) {
            return $this->loadedTemplates[$name];
        }

        $fileCache = $this->getCache();
        $isFresh = $this->isTemplateFresh($name, $fileCache->getTimestamp($path));

        if (!$isFresh || !File::isFile($path)) {
            $markup = $this->getLoader()->getMarkup($name);
            $compiled = $this->getCompiler()->compileString($markup);

            $fileCache->write($path, $compiled);
        }

        $class = $this->getTemplateClass();

        return $this->loadedTemplates[$name] = new $class($this, $path);
    }

    /**
     * Creates a template from source.
     *
     * @param string $template The template name
     *
     * @return Template
     */
    public function createTemplate($template)
    {
        $name = hash('sha256', $template, false);
        $key = $this->getCache()->getCacheKey($name, true);

        $loader = new ArrayLoader([$name => $template]);

        $current = $this->getLoader();
        $this->setLoader($loader);

        try {
            return $this->loadTemplate($name, $key);
        }
        finally {
            $this->setLoader($current);
        }
    }

    /**
     * Returns true if the template is still fresh.
     *
     * @param string $name The template name
     * @param int $time The last modification time of the cached template
     *
     * @return bool true if the template is fresh, false otherwise
     * @throws \Exception
     */
    public function isTemplateFresh($name, $time)
    {
        return $this->getLoader()->isFresh($name, $time);
    }

    /**
     * Registers a Global.
     *
     * New globals can be added before compiling or rendering a template;
     * but after, you can only update existing globals.
     *
     * @param string $name The global name
     * @param mixed $value The global value
     */
    public function addGlobal($name, $value)
    {
        self::$globalsCache[$name] = $value;
    }

    /**
     * Gets the registered Globals.
     *
     * @return array An array of globals
     */
    public function getGlobals()
    {
        return self::$globalsCache;
    }

    /**
     * Merges a context with the defined globals.
     *
     * @param array $context An array representing the context
     *
     * @return array The context merged with the globals
     */
    public function mergeGlobals(array $context)
    {
        // we don't use array_merge as the context being generally
        // bigger than globals, this code is faster.
        foreach ($this->getGlobals() as $key => $value) {
            if (!array_key_exists($key, $context)) {
                $context[$key] = $value;
            }
        }

        return $context;
    }

    public function addExtension(AbstractExtension $extension)
    {
        $class = get_class($extension);

        if (isset($this->extensions[$class])) {
            throw new LogicException(sprintf('Unable to register extension "%s" as it is already registered.', $class));
        }

        $this->extensions[$class] = $extension;
    }

    public function initExtensions()
    {
        if ($this->extensionInitialized)
            return;

        foreach ($this->extensions as $extension) {
            $this->initExtension($extension);
        }

        $this->extensionInitialized = true;
    }

    protected function initExtension(AbstractExtension $extension)
    {
        foreach ($extension->getDirectives() as $name => $callback) {
            $this->addDirective($name, $callback);
        }
    }

    protected function addDirective($name, $callback)
    {
        if (!is_callable($callback)) {
            $callback = function ($expression) use ($callback) {
                return $callback;
            };
        }

        $this->compiler->directive($name, $callback);
    }
}
