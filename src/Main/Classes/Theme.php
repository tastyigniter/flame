<?php

namespace Igniter\Main\Classes;

use Exception;
use Igniter\Flame\Igniter;
use Igniter\Flame\Pagic\Source\FileSource;
use Igniter\Flame\Support\Facades\File;
use Igniter\Main\Events\Theme\ExtendFormConfig;
use Igniter\Main\Models\Theme as ThemeModel;
use Igniter\Main\Template\Content as ContentTemplate;
use Igniter\Main\Template\Layout as LayoutTemplate;
use Igniter\Main\Template\Page as PageTemplate;
use Igniter\Main\Template\Partial as PartialTemplate;
use Igniter\System\Classes\PackageManifest;

class Theme
{
    /**
     * @var string The theme name
     */
    public $name;

    /**
     * @var string Theme label.
     */
    public $label;

    /**
     * @var string Specifies a description to accompany the theme
     */
    public $description;

    /**
     * @var string The theme author
     */
    public $author;

    /**
     * @var string The parent theme code
     */
    public $parentName;

    /**
     * @var string List of extension code and version required by this theme
     */
    public $requires = [];

    /**
     * @var string The theme path absolute base path
     */
    public $path;

    /**
     * @var string The theme relative path to the templates files
     */
    public $sourcePath;

    /**
     * @var string The theme relative path to the assets directory
     */
    public $assetPath;

    /**
     * @var string The theme path relative to base path
     */
    public $publicPath;

    /**
     * @var bool Determine if this theme is active (false) or not (true).
     */
    public $active;

    /**
     * @var string The theme author
     */
    public $locked;

    /**
     * @var string Path to the screenshot image, relative to this theme folder.
     */
    public $screenshot;

    public $config = [];

    /**
     * @var array Cached theme configuration.
     */
    protected $configCache;

    protected static $allowedTemplateModels = [
        '_layouts' => LayoutTemplate::class,
        '_pages' => PageTemplate::class,
        '_partials' => PartialTemplate::class,
        '_content' => ContentTemplate::class,
    ];

    public function __construct($path, array $config = [])
    {
        $this->path = realpath($path);
        $this->publicPath = File::localToPublic($this->path);
        $this->config = $config;
    }

    /**
     * Boots the theme.
     *
     * @return self
     */
    public function boot()
    {
        $this->fillFromConfig();
        $this->registerAsSource();
        $this->registerPathSymbol();

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getSourcePath()
    {
        return $this->path.$this->sourcePath;
    }

    /**
     * @return string
     */
    public function getAssetPath()
    {
        return $this->path.$this->sourcePath.$this->assetPath;
    }

    /**
     * @return string
     */
    public function getDirName()
    {
        return basename($this->path);
    }

    public function getParentPath()
    {
        return optional($this->getParent())->getPath();
    }

    public function getParentName()
    {
        return $this->parentName;
    }

    public function getParent()
    {
        return resolve(ThemeManager::class)->findTheme($this->getParentName());
    }

    public function hasParent()
    {
        return !is_null($this->parentName);
    }

    public function requires($require)
    {
        if (!is_array($require))
            $require = [$require];

        $this->requires = $require;

        return $this;
    }

    public function screenshot($name)
    {
        foreach ($this->getFindInPaths() as $findInPath => $publicPath) {
            foreach (['.svg', '.png', '.jpg'] as $extension) {
                if (File::isFile($findInPath.'/'.$name.$extension)) {
                    $this->screenshot = $publicPath.'/'.$name.$extension;
                    break 2;
                }
            }
        }

        return $this;
    }

    public function isActive()
    {
        return $this->active;
    }

    public function loadThemeFile()
    {
        if (File::exists($path = $this->getPath().'/theme.php'))
            require $path;

        if (File::exists($path = $this->getParentPath().'/theme.php'))
            require $path;
    }

    //
    //
    //

    public function getConfig()
    {
        if (!is_null($this->configCache))
            return $this->configCache;

        $configCache = [];
        $findInPaths = array_reverse(array_keys($this->getFindInPaths()));
        foreach ($findInPaths as $findInPath) {
            $config = File::exists($path = $findInPath.'/_meta/fields.php')
                ? File::getRequire($path) : [];

            foreach (array_get($config, 'form', []) as $key => $definitions) {
                foreach ($definitions as $index => $definition) {
                    if (!is_array($definition)) {
                        $configCache['form'][$key][$index] = $definition;
                    }
                    else {
                        foreach ($definition as $fieldIndex => $field) {
                            $configCache['form'][$key][$index][$fieldIndex] = $field;
                        }
                    }
                }
            }
        }

        return $this->configCache = $configCache;
    }

    public function getFormConfig()
    {
        $config = $this->getConfigValue('form', []);

        // @deprecated namespaced event, remove before v5
        event('main.theme.extendFormConfig', [$this->getName(), &$config]);
        event($event = new ExtendFormConfig($this->getName(), $config));

        return $event->config;
    }

    public function getConfigValue($name, $default = null)
    {
        return array_get($this->getConfig(), $name, $default);
    }

    public function hasCustomData()
    {
        return $this->getConfigValue('form', false);
    }

    public function getCustomData()
    {
        return ThemeModel::forTheme($this)->getThemeData();
    }

    /**
     * Returns variables that should be passed to the asset combiner.
     * @return array
     */
    public function getAssetVariables()
    {
        $result = [];

        $formFields = ThemeModel::forTheme($this)->getFieldsConfig();
        foreach ($formFields as $attribute => $field) {
            if (!$varNames = array_get($field, 'assetVar')) continue;

            if (!is_array($varNames))
                $varNames = [$varNames];

            foreach ($varNames as $varName) {
                $result[$varName] = $this->{$attribute};
            }
        }

        return $result;
    }

    public function fillFromConfig()
    {
        if (isset($this->config['code']))
            $this->name = $this->config['code'];

        if (isset($this->config['name']))
            $this->label = $this->config['name'];

        if (isset($this->config['parent']))
            $this->parentName = $this->config['parent'];

        if (isset($this->config['description']))
            $this->description = $this->config['description'];

        if (isset($this->config['author']))
            $this->author = $this->config['author'];

        if (isset($this->config['require']))
            $this->requires($this->config['require']);

        if (!$this->sourcePath)
            $this->sourcePath = $this->config['source-path'] ?? '';

        if (!$this->assetPath)
            $this->assetPath = $this->config['asset-path'] ?? '/assets';

        $this->screenshot('screenshot');

        if (array_key_exists('locked', $this->config))
            $this->locked = (bool)$this->config['locked'];
    }

    //
    //
    //

    public function listPages()
    {
        return PageTemplate::listInTheme($this);
    }

    public function listPartials()
    {
        return PartialTemplate::listInTheme($this);
    }

    public function listLayouts()
    {
        return LayoutTemplate::listInTheme($this);
    }

    public function getPagesOptions()
    {
    }

    public function listRequires()
    {
        return resolve(PackageManifest::class)->getCodeFromPackageName($this->requires);
    }

    //
    //
    //

    /**
     * Ensures this theme is registered as a Pagic source.
     * @return void
     */
    public function registerAsSource()
    {
        $resolver = resolve('pagic');
        if (!$resolver->hasSource($this->getName())) {
            $files = resolve('files');

            if ($this->hasParent()) {
                $source = new ChainFileSource([
                    new FileSource($this->getSourcePath(), $files),
                    new FileSource($this->getParent()->getSourcePath(), $files),
                ]);
            }
            else {
                $source = new FileSource($this->getSourcePath(), $files);
            }

            $resolver->addSource($this->getName(), $source);
        }
    }

    public function registerPathSymbol()
    {
        Igniter::loadResourcesFrom($this->getAssetPath(), $this->getName());

        if ($this->hasParent())
            Igniter::loadResourcesFrom($this->getParent()->getAssetPath(), $this->getParent()->getName());
    }

    /**
     * @param $dirName
     * @return \Igniter\Main\Template\Model|\Igniter\Flame\Pagic\Finder
     */
    public function onTemplate($dirName)
    {
        $modelClass = $this->getTemplateClass($dirName);

        return $modelClass::on($this->getName());
    }

    /**
     * @param $dirName
     * @return \Igniter\Main\Template\Model
     */
    public function newTemplate($dirName)
    {
        $class = $this->getTemplateClass($dirName);

        return new $class;
    }

    /**
     * @param $dirName
     * @return mixed
     * @throws \Exception
     */
    public function getTemplateClass($dirName)
    {
        if (!isset(self::$allowedTemplateModels[$dirName]))
            throw new Exception(sprintf('Source Model not found for [%s].', $dirName));

        return self::$allowedTemplateModels[$dirName];
    }

    /**
     * Implements the getter functionality.
     *
     * @param string $name
     *
     * @return void
     */
    public function __get($name)
    {
        if ($this->hasCustomData()) {
            return array_get($this->getCustomData(), $name);
        }

        return null;
    }

    /**
     * Determine if an attribute exists on the object.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        if ($this->hasCustomData()) {
            return array_has($this->getCustomData(), $key);
        }

        return false;
    }

    protected function getFindInPaths()
    {
        $findInPaths = [];
        $findInPaths[$this->path] = $this->publicPath;
        if ($parent = $this->getParent()) {
            $findInPaths[$parent->path] = $parent->publicPath;
        }

        return $findInPaths;
    }
}
