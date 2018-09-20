<?php

namespace Igniter\Flame\Pagic;

use Exception;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;

class Template
{
    private $env;

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
     * @internal
     *
     * @param \Igniter\Flame\Pagic\Environment $env
     * @param $path
     */
    public function __construct(Environment $env, $path)
    {
        $this->env = $env;
        $this->path = $path;
    }

    /**
     * Renders the template.
     *
     * @param array $context An array of parameters to pass to the template
     *
     * @return string The rendered template
     * @throws \Exception
     * @throws \Throwable
     */
    public function render($context = [])
    {
        $this->mergeGlobals($context);

        unset($context['this']);

        $obLevel = ob_get_level();
        ob_start();

        extract($context);

        // We'll evaluate the contents of the view inside a try/catch block so we can
        // flush out any stray output that might get out before an error occurs or
        // an exception is thrown. This prevents any partial views from leaking.
        try {
            $filePath = $this->path;
            include $filePath;
        }
        catch (Exception $e) {
            $this->handleException($e, $obLevel);
        }
        catch (Throwable $e) {
            $this->handleException(new FatalThrowableError($e), $obLevel);
        }

        return ob_get_clean();
    }

    protected function mergeGlobals($context)
    {
        if (array_key_exists('this', $context)) {
            foreach ($context['this'] as $key => $object) {
                if (property_exists($this, $key))
                    $this->{$key} = $object;
            }
        }
    }

    protected function handleException($ex, $level)
    {
        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        throw $ex;
    }
}