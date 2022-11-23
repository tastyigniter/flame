<?php

namespace Igniter\System\Traits;

use ErrorException;
use Exception;
use Igniter\Admin\Facades\Template;
use Igniter\Flame\Exception\SystemException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\View\ViewFinderInterface;
use Throwable;

trait ViewMaker
{
    /**
     * @var array A list of variables to pass to the page.
     */
    public $vars = [];

    /**
     * @var array Specifies a path to the views directory. ex. ['package::view' => 'package']
     */
    public $viewPath;

    /**
     * @var array Specifies a path to the layout directory.
     */
    public $layoutPath;

    /**
     * @var array Specifies a path to the partials directory.
     */
    public $partialPath;

    /**
     * @var string Layout to use for the view.
     */
    public $layout;

    /**
     * @var bool Prevents the use of a layout.
     */
    public $suppressLayout = false;

    public function getViewPath($view, $paths = [], $prefix = null)
    {
        if (!is_array($paths))
            $paths = [$paths];

        $guess = collect($paths)
            ->prepend($prefix, $view)
            ->reduce(function ($carry, $directory, $prefix) use ($view) {
                if (!is_null($carry)) {
                    return $carry;
                }

                $viewName = Str::after($view, $prefix.'::');

                if (view()->exists($view = $this->guessViewName($viewName, $directory))) {
                    return view()->getFinder()->find($view);
                }

                if (view()->exists($view = $this->guessViewName($viewName, $directory).'.index')) {
                    return view()->getFinder()->find($view);
                }
            });

        return $guess ?: $view;
    }

    public function guessViewName($name, $prefix = 'components.')
    {
        if ($prefix && !Str::endsWith($prefix, '.') && !Str::endsWith($prefix, '::'))
            $prefix .= '.';

        $delimiter = ViewFinderInterface::HINT_PATH_DELIMITER;

        if (Str::contains($name, $delimiter)) {
            return Str::replaceFirst($delimiter, $delimiter.$prefix, $name);
        }

        return $prefix.$name;
    }

    /**
     * Render a layout.
     *
     * @param string $name Specifies the layout name.
     * If this parameter is omitted, the $layout property will be used.
     * @param array $vars Parameter variables to pass to the view.
     * @param bool $throwException Throw an exception if the layout is not found
     *
     * @return mixed The layout contents, or false.
     * @throws \Igniter\Flame\Exception\SystemException
     */
    public function makeLayout($name = null, $vars = [], $throwException = true)
    {
        $layout = $name ?? $this->layout;
        if ($layout == '')
            return '';

        $layoutPath = $this->getViewPath(strtolower($layout), $this->layoutPath, '_layouts');

        if (!File::exists($layoutPath)) {
            if ($throwException)
                throw new SystemException(sprintf(lang('system::lang.not_found.layout'), $layout));

            return false;
        }

        return $this->makeFileContent($layoutPath, $vars);
    }

    /**
     * Loads a view with the name specified.
     * Applies layout if its name is provided by the parent object.
     * The view file must be situated in the views directory, and has the extension "htm" or "php".
     *
     * @param string $view Specifies the view name, without extension. Eg: "index".
     *
     * @return string
     */
    public function makeView($view)
    {
        $viewPath = $this->getViewPath(strtolower($view), $this->viewPath);
        $contents = $this->makeFileContent($viewPath);

        if ($this->suppressLayout || $this->layout === '')
            return $contents;

        // Append content to the body template
        Template::setBlock('body', $contents);

        return $this->makeLayout();
    }

    /**
     * Render a partial file contents located in the views or partial folder.
     *
     * @param string $partial The view to load.
     * @param array $vars Parameter variables to pass to the view.
     * @param string $prefix
     *
     * @return string Partial contents or false if not throwing an exception.
     */
    public function makePartial($partial, $vars = [], $throwException = true)
    {
        $partialPath = $this->getViewPath(strtolower($partial), $this->partialPath, '_partials');

        if (!File::exists($partialPath)) {
            if ($throwException)
                throw new SystemException(sprintf(lang('system::lang.not_found.partial'), $partial));

            return false;
        }

        if (isset($this->controller))
            $vars = array_merge($this->controller->vars, $vars);

        return $this->makeFileContent($partialPath, $vars);
    }

    /**
     * Includes a file path using output buffering.
     * Ensures that vars are available.
     *
     * @param string $filePath Absolute path to the view file.
     * @param array $extraParams Parameters that should be available to the view.
     *
     * @return string
     */
    public function makeFileContent($filePath, $extraParams = [])
    {
        if (!strlen($filePath) || $filePath == 'index.php' || !File::isFile($filePath)) {
            return '';
        }

        if (!is_array($extraParams)) {
            $extraParams = [];
        }

        $vars = array_merge($this->vars, $extraParams);

        $filePath = $this->compileFileContent($filePath);

        $vars = $this->gatherViewData($vars);

        $obLevel = ob_get_level();

        ob_start();

        extract($vars);

        // We'll evaluate the contents of the view inside a try/catch block so we can
        // flush out any stray output that might get out before an error occurs or
        // an exception is thrown. This prevents any partial views from leaking.
        try {
            include $filePath;
        }
        catch (Exception $e) {
            $this->handleViewException($e, $obLevel);
        }
        catch (Throwable $e) {
            $this->handleViewException(new ErrorException($e), $obLevel);
        }

        return ob_get_clean();
    }

    public function compileFileContent($filePath)
    {
        $compiler = resolve('blade.compiler');

        if ($compiler->isExpired($filePath)) {
            $compiler->compile($filePath);
        }

        return $compiler->getCompiledPath($filePath);
    }

    /**
     * Handle a view exception.
     *
     * @param \Exception $e
     * @param int $obLevel
     *
     * @return void
     */
    protected function handleViewException($e, $obLevel)
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }

    /**
     * Get the data bound to the view instance.
     *
     * @param $data
     * @return array
     */
    protected function gatherViewData($data)
    {
        $data = array_merge(View::getShared(), $data);

        return array_map(function ($value) {
            if ($value instanceof Renderable)
                return $value->render();

            return $value;
        }, $data);
    }
}
