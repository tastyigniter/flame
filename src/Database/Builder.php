<?php

namespace Igniter\Flame\Database;

use Illuminate\Database\Eloquent\Builder as BuilderBase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * TastyIgniter Database Manager Class
 * @package        Igniter\Flame\Database\Manager.php
 */
class Builder extends BuilderBase
{
    /**
     * Get an array with the values of a given column.
     *
     * @param  string $column
     * @param  string|null $key
     *
     * @return \Illuminate\Support\Collection
     */
    public function lists($column, $key = null)
    {
        return $this->pluck($column, $key);
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param  string $column
     * @param  string|null $key
     *
     * @return \Illuminate\Support\Collection
     */
    public function dropdown($column, $key = null)
    {
        $key = !is_null($key) ? $key : $this->model->getKeyName();

        return $this->lists($column, $key);
    }

    /**
     * Perform a search on this query for term found in columns.
     *
     * @param  string $term Search query
     * @param  array $columns Table columns to search
     * @param  string $mode Search mode: all, any, exact.
     *
     * @return self
     */
    public function search($term, $columns = [], $mode = 'all')
    {
        return $this->searchInternal($term, $columns, $mode, 'and');
    }

    /**
     * Add an "or search where" clause to the query.
     *
     * @param  string $term Search query
     * @param  array $columns Table columns to search
     * @param  string $mode Search mode: all, any, exact.
     *
     * @return self
     */
    public function orSearch($term, $columns = [], $mode = 'all')
    {
        return $this->searchInternal($term, $columns, $mode, 'or');
    }

    /**
     * Convenient method for where like clause
     *
     * @param  string $column
     * @param $value
     * @param string $side
     * @param string $boolean
     *
     * @return \Igniter\Flame\Database\Builder
     */
    public function like($column, $value, $side = 'both', $boolean = 'and')
    {
        return $this->likeInternal($column, $value, $side, $boolean);
    }

    /**
     * Convenient method for or where like clause
     *
     * @param  string $column
     * @param $value
     * @param string $side
     *
     * @return self
     */
    public function orLike($column, $value, $side = 'both')
    {
        return $this->likeInternal($column, $value, $side, 'or');
    }

    /**
     * Internal method to apply a search constraint to the query.
     * Mode can be any of these options:
     * - all: result must contain all words
     * - any: result can contain any word
     * - exact: result must contain the exact phrase
     *
     * @param $term
     * @param array $columns
     * @param $mode
     * @param $boolean
     *
     * @return $this
     */
    protected function searchInternal($term, $columns = [], $mode, $boolean)
    {
        if (!is_array($columns))
            $columns = [$columns];

        if (!$mode)
            $mode = 'all';

        if ($mode === 'exact') {
            $this->where(function ($query) use ($columns, $term) {
                foreach ($columns as $field) {
                    if (!strlen($term)) continue;
                    $query->orLike($field, $term, 'both');
                }
            }, null, null, $boolean);
        }
        else {
            $words = explode(' ', $term);
            $wordBoolean = $mode === 'any' ? 'or' : 'and';

            $this->where(function ($query) use ($columns, $words, $wordBoolean) {
                foreach ($columns as $field) {
                    $query->orWhere(function ($query) use ($field, $words, $wordBoolean) {
                        foreach ($words as $word) {
                            if (!strlen($word)) continue;
                            $query->like($field, $word, 'both', $wordBoolean);
                        }
                    });
                }
            }, null, null, $boolean);
        }

        return $this;
    }

    protected function likeInternal($column, $value, $side = null, $boolean = 'and')
    {
        $column = $this->toBase()->raw(sprintf("lower(%s)", $column));
        $value = trim(mb_strtolower($value));

        if ($side === 'none') {
            $value = $value;
        }
        elseif ($side === 'before') {
            $value = "%{$value}";
        }
        elseif ($side === 'after') {
            $value = "{$value}%";
        }
        else {
            $value = "%{$value}%";
        }

        return $this->where($column, 'like', $value, $boolean);
    }

    /**
     * Get an array with the values of dates.
     *
     * @param  string $column
     * @param string $keyFormat
     * @param string $valueFormat
     *
     * @return array
     */
    public function pluckDates($column, $keyFormat = 'Y-m', $valueFormat = 'F Y')
    {
        $dates = [];

        $collection = $this->selectRaw("{$column}, MONTH({$column}) as month, YEAR({$column}) as year")
                           ->groupBy([$column, 'month', 'year'])->orderBy($column, 'desc')->get();

        if ($collection) {
            foreach ($collection as $model) {
                $date = $model[$column];
                if (!($date instanceof \Carbon\Carbon))
                    $date = \Carbon\Carbon::parse($date);

                $key = $date->format($keyFormat);
                $value = $date->format($valueFormat);
                $dates[$key] = $value;
            }
        }

        return $dates;
    }

    /**
     * Paginate the given query.
     *
     * @param  int $perPage
     * @param  array $columns
     * @param  string $pageName
     * @param  int|null $page
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws \InvalidArgumentException
     */
    public function paginate($perPage = null, $page = null, $columns = ['*'], $pageName = 'page')
    {
        if (is_array($page)) {
            $columns = $page;
            $page = null;
        }

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->model->getPerPage();

        $results = ($total = $this->toBase()->getCountForPagination())
            ? $this->forPage($page, $perPage)->get($columns)
            : $this->model->newCollection();

        return new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param  int $perPage
     * @param  array $columns
     * @param  string $pageName
     * @param  int|null $page
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = null, $page = null, $columns = ['*'], $pageName = 'page')
    {
        if (is_array($page)) {
            $columns = $page;
            $page = null;
        }

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->model->getPerPage();

        // Next we will set the limit and offset for this query so that when we get the
        // results we get the proper section of results. Then, we'll create the full
        // paginator instances for these results with the given page and per page.
        $this->skip(($page - 1) * $perPage)->take($perPage + 1);

        return new Paginator($this->get($columns), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }

    /**
     * Find a model by its primary key or return fresh model instance
     * with filled attributes to use with forms.
     *
     * @param  mixed $id
     * @param  array $columns
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function findOrNew($id, $columns = ['*'])
    {
        if (!is_null($model = $this->find($id, $columns))) {
            return $model;
        }

        $attributes = $this->toBase()->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());

        return $this->model->newInstance(array_fill_keys(array_values($attributes), null))->setConnection(
            $this->toBase()->getConnection()->getName()
        );
    }
}