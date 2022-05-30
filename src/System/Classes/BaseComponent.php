<?php

namespace Igniter\System\Classes;

use BadMethodCallException;
use Igniter\Flame\Pagic\TemplateCode;
use Igniter\Flame\Support\Extendable;
use Igniter\Flame\Traits\EventEmitter;
use Igniter\Main\Classes\MainController;
use Igniter\System\Traits\AssetMaker;
use Igniter\System\Traits\PropertyContainer;
use Illuminate\Support\Facades\Lang;

/**
 * Base Component Class
 */
abstract class BaseComponent extends Extendable
{
    use EventEmitter;
    use AssetMaker;
    use PropertyContainer;

    public $defaultPartial = 'default';

    /**
     * @var string Alias used for this component.
     */
    public $alias;

    /**
     * @var string Component class name or class alias.
     */
    public $name;

    /**
     * @var bool Determines whether the component is hidden from the admin UI.
     */
    public $isHidden = false;

    /**
     * @var string Icon of the extension that defines the component.
     * This field is used internally.
     */
    public $extensionIcon;

    /**
     * @var string Specifies the component directory name.
     */
    protected $dirName;

    /**
     * @var array Holds the component layout settings array.
     */
    protected $properties;

    protected $viewPath;

    /**
     * @var MainController Controller object.
     */
    protected $controller;

    /**
     * @var \Igniter\Main\Template\Page Page template object.
     */
    protected $page;

    /**
     * Class constructor
     *
     * @param \Igniter\Flame\Pagic\TemplateCode $page
     * @param array $properties
     */
    public function __construct($page = null, $properties = [])
    {
        if ($page instanceof TemplateCode) {
            $this->page = $page;
            $this->controller = $page->controller;
        }

        $this->properties = $this->validateProperties($properties);

        $this->dirName = strtolower(str_replace('\\', '/', get_called_class()));
        $namespace = implode('.', array_slice(explode('/', $this->dirName), 0, 2));
        $this->assetPath[] = $namespace.'::assets/'.basename($this->dirName);
        $this->assetPath[] = $namespace.'::assets';
        $this->assetPath[] = $namespace.'::';

        parent::__construct();
    }

    /**
     * Returns the absolute component view path.
     */
    public function getPath()
    {
        if ($this->viewPath)
            return $this->viewPath;

        $namespace = implode('.', array_slice(explode('/', $this->dirName), 0, 2));

        return $namespace.'::views/_components/'.basename($this->dirName);
    }

    /**
     * Executed when this component is first initialized, before AJAX requests.
     */
    public function initialize()
    {
    }

    /**
     * Executed when this component is bound to a layout.
     */
    public function onRun()
    {
    }

    /**
     * Executed when this component is rendered on a layout.
     */
    public function onRender()
    {
    }

    /**
     * Renders a requested partial in context of this component,
     * @return mixed
     * @see \Igniter\Main\Classes\MainController::renderPartial for usage.
     */
    public function renderPartial()
    {
        $this->controller->setComponentContext($this);
        $result = call_user_func_array([$this->controller, 'renderPartial'], func_get_args());
        $this->controller->setComponentContext(null);

        return $result;
    }

    /**
     * Executes an AJAX handler.
     */
    public function runEventHandler($handler)
    {
        $result = $this->{$handler}();

        $this->fireSystemEvent('main.component.afterRunEventHandler', [$handler, &$result]);

        return $result;
    }

    public function getEventHandler($handler)
    {
        return $this->alias.'::'.$handler;
    }

    //
    // Property helpers
    //

    public function param($name, $default = null)
    {
        $segment = $this->controller->param($name);
        if (is_null($segment))
            $segment = input($name);

        return is_null($segment) ? $default : $segment;
    }

    //
    // Magic methods
    //

    /**
     * Dynamically handle calls into the controller instance.
     *
     * @param string $name
     * @param array $params
     *
     * @return mixed
     */
    public function __call($name, $params)
    {
        try {
            return parent::__call($name, $params);
        }
        catch (BadMethodCallException $ex) {
        }

        if (method_exists($this->controller, $name)) {
            return call_user_func_array([$this->controller, $name], $params);
        }

        throw new BadMethodCallException(Lang::get('igniter::main.not_found.method', [
            'name' => get_class($this),
            'method' => $name,
        ]));
    }

    public function __toString()
    {
        return $this->alias;
    }
}
