<?php

namespace Igniter\Flame\Pagic;

use ErrorException;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\View;
use Throwable;

class Template
{
    private $env;

    /**
     * A stack of the last compiled templates.
     *
     * @var array
     */
    private $lastCompiled = [];

    protected $path;

    protected $page;

    protected $layout;

    protected $theme;

    protected $param;

    protected $controller;

    protected $session;

    /**
     * This method is for internal use only and should never be called
     * directly (use Environment::load() instead).
     * @param \Igniter\Flame\Pagic\Environment $env
     * @param $path
     * @internal
     */
    public function __construct(Environment $env, $path)
    {
        $this->env = $env;
        $this->path = $path;
    }

    /**
     * Renders the template.
     *
     * @param array $data An array of parameters to pass to the template
     *
     * @return string The rendered template
     * @throws \Exception
     * @throws \Throwable
     */
    public function render($data = [])
    {
        $this->lastCompiled[] = $this->getSourceFilePath();

        $this->mergeGlobals($data);

        unset($data['this']);

        $results = $this->getContents($data);

        array_pop($this->lastCompiled);

        return $results;
    }

    protected function mergeGlobals($data)
    {
        if (array_key_exists('this', $data)) {
            foreach ($data['this'] as $key => $object) {
                if (property_exists($this, $key))
                    $this->{$key} = $object;
            }
        }
    }

    protected function getContents($data)
    {
        return $this->evaluatePath($this->path, $this->gatherData($data));
    }

    /**
     * Get the data bound to the view instance.
     *
     * @param $data
     * @return array
     */
    protected function gatherData($data)
    {
        $data = array_merge(View::getShared(), $data);

        return array_map(function ($value) {
            if ($value instanceof Renderable)
                return $value->render();

            return $value;
        }, $data);
    }

    protected function evaluatePath($path, $data)
    {
        if ($silenceNotice = config('system.suppressTemplateRuntimeNotice')) {
            $errorLevel = error_reporting(0);
        }

        $obLevel = ob_get_level();
        ob_start();

        extract($data, EXTR_SKIP);

        // We'll evaluate the contents of the view inside a try/catch block so we can
        // flush out any stray output that might get out before an error occurs or
        // an exception is thrown. This prevents any partial views from leaking.
        try {
            include $path;
        }
        catch (Exception|Throwable $e) {
            $this->handleException($e, $obLevel);
        }

        if ($silenceNotice) {
            error_reporting($errorLevel);
        }

        return ltrim(ob_get_clean());
    }

    protected function handleException($ex, $level)
    {
        $ex = new ErrorException($this->getMessage($ex), 0, 1, $ex->getFile(), $ex->getLine(), $ex);

        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        throw $ex;
    }

    /**
     * Get the exception message for an exception.
     *
     * @param \Exception $e
     * @return string
     */
    protected function getMessage($e)
    {
        return $e->getMessage().' (View: '.realpath(last($this->lastCompiled)).')';
    }

    protected function getSourceFilePath()
    {
        if ($source = $this->env->getLoader()->getSource())
            return $source->getFilePath();

        return $this->path;
    }
}
