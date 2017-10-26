<?php

namespace Igniter\Flame\Pagic\Source;

interface SourceResolverInterface
{
    /**
     * Get a source instance.
     *
     * @param  string $name
     *
     * @return \Igniter\Flame\Pagic\Source\SourceInterface
     */
    public function source($name = null);

    /**
     * Get the default source name.
     * @return string
     */
    public function getDefaultSourceName();

    /**
     * Set the default source name.
     *
     * @param  string $name
     *
     * @return void
     */
    public function setDefaultSourceName($name);
}