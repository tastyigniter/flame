<?php

namespace Igniter\Flame\Database\Connections;

use Igniter\Flame\Database\MemoryCache;
use Igniter\Flame\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Connection as ConnectionBase;

class Connection extends ConnectionBase
{
    /**
     * Get a new query builder instance.
     *
     * @return \Igniter\Flame\Database\Query\Builder
     */
    public function query()
    {
        return new QueryBuilder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }

    /**
     * Flush the memory cache.
     * @return void
     */
    public static function flushDuplicateCache()
    {
        MemoryCache::instance()->flush();
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param string $query
     * @param array $bindings
     * @param float|null $time
     * @return void
     */
    public function logQuery($query, $bindings, $time = null)
    {
        if (isset($this->events)) {
            $this->events->dispatch('illuminate.query', [$query, $bindings, $time, $this->getName()]);
        }

        parent::logQuery($query, $bindings, $time);
    }

    /**
     * Fire an event for this connection.
     *
     * @param string $event
     * @return void
     */
    protected function fireConnectionEvent($event)
    {
        if (isset($this->events)) {
            $this->events->dispatch('connection.'.$this->getName().'.'.$event, $this);
        }

        parent::fireConnectionEvent($event);
    }
}
