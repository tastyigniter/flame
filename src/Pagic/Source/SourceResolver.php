<?php

namespace Igniter\Flame\Pagic\Source;

class SourceResolver implements SourceResolverInterface
{
    /**
     * All of the registered sources.
     *
     * @var array
     */
    protected $sources = [];

    /**
     * The default source name.
     *
     * @var string
     */
    protected $default;

    /**
     * Create a new source resolver instance.
     *
     * @param  array $sources
     */
    public function __construct(array $sources = [])
    {
        foreach ($sources as $name => $source) {
            $this->addSource($name, $source);
        }
    }

    /**
     * Get a source instance.
     *
     * @param  string $name
     *
     * @return \Igniter\Flame\Pagic\Source\SourceInterface
     */
    public function source($name = null)
    {
        if (is_null($name)) {
            $name = $this->getDefaultSourceName();
        }

        return $this->sources[$name];
    }

    /**
     * Add a source to the resolver.
     *
     * @param  string $name
     * @param \Igniter\Flame\Pagic\Source\SourceInterface $source
     *
     * @return void
     */
    public function addSource($name, SourceInterface $source)
    {
        $this->sources[$name] = $source;
    }

    /**
     * Check if a source has been registered.
     *
     * @param  string $name
     *
     * @return bool
     */
    public function hasSource($name)
    {
        return isset($this->sources[$name]);
    }

    /**
     * Get the default source name.
     * @return string
     */
    public function getDefaultSourceName()
    {
        return $this->default;
    }

    /**
     * Set the default source name.
     *
     * @param  string $name
     *
     * @return void
     */
    public function setDefaultSourceName($name)
    {
        $this->default = $name;
    }
}