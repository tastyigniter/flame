<?php namespace Igniter\Flame\Pagic;

use Exception;

/**
 * Loads a template from an array.
 * @method \Igniter\Flame\Pagic\Contracts\TemplateSource getSource()
 */
class ArrayLoader extends Loader
{
    protected $templates = [];

    /**
     * @param array $templates An array of templates (keys are the names, and values are the source code)
     */
    public function __construct(array $templates = [])
    {
        $this->templates = $templates;
    }

    /**
     * Adds or overrides a template.
     *
     * @param string $name The template name
     * @param string $template The template source
     */
    public function setTemplate($name, $template)
    {
        $this->templates[$name] = $template;
    }

    public function getFilename($name)
    {
        return $name;
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
        return array_get($this->templates, $name);
    }

    /**
     * Gets the source code of a template, given its name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The template source code
     *
     * @throws Exception When $name is not found
     */
    public function getContents($name)
    {
        return array_get($this->templates, $name);
    }

    public function exists($name)
    {
        return isset($this->templates[$name]);
    }

    public function getCacheKey($name)
    {
        if (!isset($this->templates[$name])) {
            throw new Exception(sprintf('Template "%s" is not defined.', $name));
        }

        return $name.':'.$this->templates[$name];
    }

    public function isFresh($name, $time)
    {
        if (!isset($this->templates[$name])) {
            throw new Exception(sprintf('Template "%s" is not defined.', $name));
        }

        return TRUE;
    }

    public function getFilePath()
    {
        return null;
    }
}
