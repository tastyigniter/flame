<?php

namespace Igniter\Flame\Database\Connections;

use Igniter\Flame\Database\Query\Builder as QueryBuilder;

class MySqlConnection extends \Illuminate\Database\MySqlConnection
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
}
