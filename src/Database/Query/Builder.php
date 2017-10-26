<?php

namespace Igniter\Flame\Database\Query;

class Builder extends \Illuminate\Database\Query\Builder
{
    /**
     * Get an array with the values of a given column.
     *
     * @param  string $column
     * @param  string|null $key
     *
     * @return \Illuminate\Support\Collection
     */
//    public function dropdown($column, $key = null)
//    {
//        $key = !is_null($key) ? $key : $this->model->getKeyName();
//
//        return $this->pluck($column, $key);
//    }
}