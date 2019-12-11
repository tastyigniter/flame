<?php

namespace Igniter\Flame\Pagic\Source;

abstract class AbstractSource
{
    /**
     * The query post processor implementation.
     *
     * @var \Igniter\Flame\Pagic\Processors\Processor
     */
    protected $processor;

    /**
     * Get the query post processor used by the connection.
     * @return \Igniter\Flame\Pagic\Processors\Processor
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * Generate a cache key unique to this source.
     *
     * @param string $name
     *
     * @return int|string
     */
    public function makeCacheKey($name = '')
    {
        return crc32($name);
    }
}