<?php

namespace Igniter\Flame\Database\NestedSet;

use Igniter\Flame\Database\Builder as BuilderBase;
use Igniter\Flame\Database\Traits\NestedTree;
use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder as BaseQueryBuilder;
use Illuminate\Database\Query\Builder as Query;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Kalnoy\Nestedset\Collection;
use Kalnoy\Nestedset\NestedSet;
use LogicException;

class Builder extends BuilderBase
{
    /**
     * @var NestedTree|ModelBase
     */
    protected $model;

    /**
     * Get node's `lft` and `rgt` values.
     *
     * @param mixed $id
     * @param bool $required
     *
     * @return array
     * @since 2.0
     */
    public function getNodeData($id, $required = false)
    {
        $query = $this->toBase();

        $query->where($this->model->getKeyName(), '=', $id);

        $data = $query->first([
            $this->model->getLftName(), $this->model->getRgtName(),
        ]);

        if (!$data && $required) {
            throw new ModelNotFoundException;
        }

        return (array)$data;
    }

    /**
     * Get plain node data.
     *
     * @param mixed $id
     * @param bool $required
     *
     * @return array
     * @since 2.0
     */
    public function getPlainNodeData($id, $required = false)
    {
        return array_values($this->getNodeData($id, $required));
    }

    /**
     * Scope limits query to select just root node.
     *
     * @return $this
     */
    public function whereIsRoot()
    {
        $this->query->whereNull($this->model->getParentIdName());

        return $this;
    }

    /**
     * Limit results to ancestors of specified node.
     *
     * @param mixed $id
     * @param bool $andSelf
     *
     * @param string $boolean
     *
     * @return $this
     * @since 2.0
     */
    public function whereAncestorOf($id, $andSelf = false, $boolean = 'and')
    {
        $keyName = $this->model->getKeyName();

        if (NestedSet::isNode($id)) {
            $value = '?';

            $this->query->addBinding($id->getRgt());

            $id = $id->getKey();
        }
        else {
            $valueQuery = $this->model
                ->newQuery()
                ->toBase()
                ->select('_.'.$this->model->getRgtName())
                ->from($this->model->getTable().' as _')
                ->where($keyName, '=', $id)
                ->limit(1);

            $this->query->mergeBindings($valueQuery);

            $value = '('.$valueQuery->toSql().')';
        }

        $this->query->whereNested(function ($inner) use ($value, $andSelf, $id) {
            [$lft, $rgt] = $this->wrappedColumns();

            $inner->whereRaw("{$value} between {$lft} and {$rgt}");

            if (!$andSelf) {
                $inner->where($this->model->getKeyName(), '<>', $id);
            }
        }, $boolean);

        return $this;
    }

    /**
     * @param $id
     * @param bool $andSelf
     *
     * @return $this
     */
    public function orWhereAncestorOf($id, $andSelf = false)
    {
        return $this->whereAncestorOf($id, $andSelf, 'or');
    }

    /**
     * @param $id
     *
     * @return \Igniter\Flame\Database\NestedSet\Builder
     */
    public function whereAncestorOrSelf($id)
    {
        return $this->whereAncestorOf($id, true);
    }

    /**
     * Get ancestors of specified node.
     *
     * @param mixed $id
     * @param array $columns
     *
     * @return \Kalnoy\Nestedset\Collection
     * @since 2.0
     */
    public function ancestorsOf($id, array $columns = ['*'])
    {
        return $this->whereAncestorOf($id)->get($columns);
    }

    /**
     * @param $id
     * @param array $columns
     *
     * @return \Kalnoy\Nestedset\Collection
     */
    public function ancestorsAndSelf($id, array $columns = ['*'])
    {
        return $this->whereAncestorOf($id, true)->get($columns);
    }

    /**
     * Add node selection statement between specified range.
     *
     * @param array $values
     * @param string $boolean
     * @param bool $not
     *
     * @return $this
     * @since 2.0
     */
    public function whereNodeBetween($values, $boolean = 'and', $not = false)
    {
        $this->query->whereBetween($this->model->getLftName(), $values, $boolean, $not);

        return $this;
    }

    /**
     * Add node selection statement between specified range joined with `or` operator.
     *
     * @param array $values
     *
     * @return $this
     * @since 2.0
     */
    public function orWhereNodeBetween($values)
    {
        return $this->whereNodeBetween($values, 'or');
    }

    /**
     * Add constraint statement to descendants of specified node.
     *
     * @param mixed $id
     * @param string $boolean
     * @param bool $not
     * @param bool $andSelf
     *
     * @return $this
     * @since 2.0
     */
    public function whereDescendantOf($id, $boolean = 'and', $not = false,
                                      $andSelf = false
    ) {
        if (NestedSet::isNode($id)) {
            $data = $id->getBounds();
        }
        else {
            $data = $this->model->newNestedSetQuery()
                ->getPlainNodeData($id, true);
        }

        // Don't include the node
        if (!$andSelf) {
            $data[0]++;
        }

        return $this->whereNodeBetween($data, $boolean, $not);
    }

    /**
     * @param mixed $id
     *
     * @return \Igniter\Flame\Database\NestedSet\Builder
     */
    public function whereNotDescendantOf($id)
    {
        return $this->whereDescendantOf($id, 'and', true);
    }

    /**
     * @param mixed $id
     *
     * @return \Igniter\Flame\Database\NestedSet\Builder
     */
    public function orWhereDescendantOf($id)
    {
        return $this->whereDescendantOf($id, 'or');
    }

    /**
     * @param mixed $id
     *
     * @return \Igniter\Flame\Database\NestedSet\Builder
     */
    public function orWhereNotDescendantOf($id)
    {
        return $this->whereDescendantOf($id, 'or', true);
    }

    /**
     * @param $id
     * @param string $boolean
     * @param bool $not
     *
     * @return $this
     */
    public function whereDescendantOrSelf($id, $boolean = 'and', $not = false)
    {
        return $this->whereDescendantOf($id, $boolean, $not, true);
    }

    /**
     * Get descendants of specified node.
     *
     * @param mixed $id
     * @param array $columns
     * @param bool $andSelf
     *
     * @return Collection
     * @since 2.0
     */
    public function descendantsOf($id, array $columns = ['*'], $andSelf = false)
    {
        try {
            return $this->whereDescendantOf($id, 'and', false, $andSelf)->get($columns);
        }
        catch (ModelNotFoundException $e) {
            return $this->model->newCollection();
        }
    }

    /**
     * @param $id
     * @param array $columns
     *
     * @return Collection
     */
    public function descendantsAndSelf($id, array $columns = ['*'])
    {
        return $this->descendantsOf($id, $columns, true);
    }

    /**
     * @param $id
     * @param $operator
     * @param $boolean
     *
     * @return $this
     */
    protected function whereIsBeforeOrAfter($id, $operator, $boolean)
    {
        if (NestedSet::isNode($id)) {
            $value = '?';

            $this->query->addBinding($id->getLft());
        }
        else {
            $valueQuery = $this->model
                ->newQuery()
                ->toBase()
                ->select('_n.'.$this->model->getLftName())
                ->from($this->model->getTable().' as _n')
                ->where('_n.'.$this->model->getKeyName(), '=', $id);

            $this->query->mergeBindings($valueQuery);

            $value = '('.$valueQuery->toSql().')';
        }

        [$lft,] = $this->wrappedColumns();

        $this->query->whereRaw("{$lft} {$operator} {$value}", [], $boolean);

        return $this;
    }

    /**
     * Constraint nodes to those that are after specified node.
     *
     * @param mixed $id
     * @param string $boolean
     *
     * @return $this
     * @since 2.0
     */
    public function whereIsAfter($id, $boolean = 'and')
    {
        return $this->whereIsBeforeOrAfter($id, '>', $boolean);
    }

    /**
     * Constraint nodes to those that are before specified node.
     *
     * @param mixed $id
     * @param string $boolean
     *
     * @return $this
     * @since 2.0
     */
    public function whereIsBefore($id, $boolean = 'and')
    {
        return $this->whereIsBeforeOrAfter($id, '<', $boolean);
    }

    /**
     * @return $this
     */
    public function whereIsLeaf()
    {
        [$lft, $rgt] = $this->wrappedColumns();

        return $this->whereRaw("$lft = $rgt - 1");
    }

    /**
     * @param array $columns
     *
     * @return \Igniter\Flame\Database\NestedSet\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function leaves(array $columns = ['*'])
    {
        return $this->whereIsLeaf()->get($columns);
    }

    /**
     * Include depth level into the result.
     *
     * @param string $as
     *
     * @return $this
     */
    public function withDepth($as = 'depth')
    {
        if ($this->query->columns === null) $this->query->columns = ['*'];

        $table = $this->wrappedTable();

        [$lft, $rgt] = $this->wrappedColumns();

        $alias = '_d';
        $wrappedAlias = $this->query->getGrammar()->wrapTable($alias);

        $query = $this->model
            ->newScopedQuery('_d')
            ->toBase()
            ->selectRaw('count(1) - 1')
            ->from($this->model->getTable().' as '.$alias)
            ->whereRaw("{$table}.{$lft} between {$wrappedAlias}.{$lft} and {$wrappedAlias}.{$rgt}");

        $this->query->selectSub($query, $as);

        return $this;
    }

    /**
     * Get wrapped `lft` and `rgt` column names.
     *
     * @return array
     * @since 2.0
     */
    protected function wrappedColumns()
    {
        $grammar = $this->query->getGrammar();

        return [
            $grammar->wrap($this->model->getLftName()),
            $grammar->wrap($this->model->getRgtName()),
        ];
    }

    /**
     * Get a wrapped table name.
     *
     * @return string
     * @since 2.0
     */
    protected function wrappedTable()
    {
        return $this->query->getGrammar()->wrapTable($this->getQuery()->from);
    }

    /**
     * Wrap model's key name.
     *
     * @return string
     * @since 2.0
     */
    protected function wrappedKey()
    {
        return $this->query->getGrammar()->wrap($this->model->getKeyName());
    }

    /**
     * Exclude root node from the result.
     *
     * @return $this
     */
    public function withoutRoot()
    {
        $this->query->whereNotNull($this->model->getParentIdName());

        return $this;
    }

    /**
     * Equivalent of `withoutRoot`.
     *
     * @return $this
     * @deprecated since v4.1
     *
     * @since 2.0
     */
    public function hasParent()
    {
        $this->query->whereNotNull($this->model->getParentIdName());

        return $this;
    }

    /**
     * Get only nodes that have children.
     *
     * @return $this
     * @deprecated since v4.1
     *
     * @since 2.0
     */
    public function hasChildren()
    {
        [$lft, $rgt] = $this->wrappedColumns();

        $this->query->whereRaw("{$rgt} > {$lft} + 1");

        return $this;
    }

    /**
     * Order by node position.
     *
     * @param string $dir
     *
     * @return $this
     */
    public function defaultOrder($dir = 'asc')
    {
        $this->query->orders = null;

        $this->query->orderBy($this->model->getLftName(), $dir);

        return $this;
    }

    /**
     * Order by reversed node position.
     *
     * @return $this
     */
    public function reversed()
    {
        return $this->defaultOrder('desc');
    }

    /**
     * Move a node to the new position.
     *
     * @param mixed $key
     * @param int $position
     *
     * @return int
     */
    public function moveNode($key, $position)
    {
        [$lft, $rgt] = $this->model->newNestedSetQuery()
            ->getPlainNodeData($key, true);

        if ($lft < $position && $position <= $rgt) {
            throw new LogicException('Cannot move node into itself.');
        }

        // Get boundaries of nodes that should be moved to new position
        $from = min($lft, $position);
        $to = max($rgt, $position - 1);

        // The height of node that is being moved
        $height = $rgt - $lft + 1;

        // The distance that our node will travel to reach it's destination
        $distance = $to - $from + 1 - $height;

        // If no distance to travel, just return
        if ($distance === 0) {
            return 0;
        }

        if ($position > $lft) {
            $height *= -1;
        }
        else {
            $distance *= -1;
        }

        $params = compact('lft', 'rgt', 'from', 'to', 'height', 'distance');

        $boundary = [$from, $to];

        $query = $this->toBase()->where(function (Query $inner) use ($boundary) {
            $inner->whereBetween($this->model->getLftName(), $boundary);
            $inner->orWhereBetween($this->model->getRgtName(), $boundary);
        });

        return $query->update($this->patch($params));
    }

    /**
     * Make or remove gap in the tree. Negative height will remove gap.
     *
     * @param int $cut
     * @param int $height
     *
     * @return int
     * @since 2.0
     */
    public function makeGap($cut, $height)
    {
        $params = compact('cut', 'height');

        $query = $this->toBase()->whereNested(function (Query $inner) use ($cut) {
            $inner->where($this->model->getLftName(), '>=', $cut);
            $inner->orWhere($this->model->getRgtName(), '>=', $cut);
        });

        return $query->update($this->patch($params));
    }

    /**
     * Get patch for columns.
     *
     * @param array $params
     *
     * @return array
     * @since 2.0
     */
    protected function patch(array $params)
    {
        $grammar = $this->query->getGrammar();

        $columns = [];

        foreach ([$this->model->getLftName(), $this->model->getRgtName()] as $col) {
            $columns[$col] = $this->columnPatch($grammar->wrap($col), $params);
        }

        return $columns;
    }

    /**
     * Get patch for single column.
     *
     * @param string $col
     * @param array $params
     *
     * @return string
     * @since 2.0
     */
    protected function columnPatch($col, array $params)
    {
        extract($params);

        /** @var int $height */
        if ($height > 0) $height = '+'.$height;

        if (isset($cut)) {
            return new Expression("case when {$col} >= {$cut} then {$col}{$height} else {$col} end");
        }

        /** @var int $distance */
        /** @var int $lft */
        /** @var int $rgt */
        /** @var int $from */
        /** @var int $to */
        if ($distance > 0) $distance = '+'.$distance;

        return new Expression('case '.
            "when {$col} between {$lft} and {$rgt} then {$col}{$distance} ". // Move the node
            "when {$col} between {$from} and {$to} then {$col}{$height} ". // Move other nodes
            "else {$col} end"
        );
    }

    /**
     * Get statistics of errors of the tree.
     *
     * @return array
     * @since 2.0
     */
    public function countErrors()
    {
        $checks = [];

        // Check if lft and rgt values are ok
        $checks['oddness'] = $this->getOdnessQuery();

        // Check if lft and rgt values are unique
        $checks['duplicates'] = $this->getDuplicatesQuery();

        // Check if parent_id is set correctly
        $checks['wrong_parent'] = $this->getWrongParentQuery();

        // Check for nodes that have missing parent
        $checks['missing_parent'] = $this->getMissingParentQuery();

        $query = $this->query->newQuery();

        foreach ($checks as $key => $inner) {
            $inner->selectRaw('count(1)');

            $query->selectSub($inner, $key);
        }

        return (array)$query->first();
    }

    /**
     * @return BaseQueryBuilder
     */
    protected function getOdnessQuery()
    {
        return $this->model
            ->newNestedSetQuery()
            ->toBase()
            ->whereNested(function (BaseQueryBuilder $inner) {
                [$lft, $rgt] = $this->wrappedColumns();

                $inner->whereRaw("{$lft} >= {$rgt}")
                    ->orWhereRaw("({$rgt} - {$lft}) % 2 = 0");
            });
    }

    /**
     * @return BaseQueryBuilder
     */
    protected function getDuplicatesQuery()
    {
        $table = $this->wrappedTable();

        $firstAlias = 'c1';
        $secondAlias = 'c2';

        $waFirst = $this->query->getGrammar()->wrapTable($firstAlias);
        $waSecond = $this->query->getGrammar()->wrapTable($secondAlias);

        $query = $this->model
            ->newNestedSetQuery($firstAlias)
            ->toBase()
            ->from($this->query->raw("{$table} as {$waFirst}, {$table} {$waSecond}"))
            ->whereRaw("{$waFirst}.id < {$waSecond}.id")
            ->whereNested(function (BaseQueryBuilder $inner) use ($waFirst, $waSecond) {
                [$lft, $rgt] = $this->wrappedColumns();

                $inner->orWhereRaw("{$waFirst}.{$lft}={$waSecond}.{$lft}")
                    ->orWhereRaw("{$waFirst}.{$rgt}={$waSecond}.{$rgt}")
                    ->orWhereRaw("{$waFirst}.{$lft}={$waSecond}.{$rgt}")
                    ->orWhereRaw("{$waFirst}.{$rgt}={$waSecond}.{$lft}");
            });

        return $this->model->applyNestedSetScope($query, $secondAlias);
    }

    /**
     * @return BaseQueryBuilder
     */
    protected function getWrongParentQuery()
    {
        $table = $this->wrappedTable();
        $keyName = $this->wrappedKey();

        $grammar = $this->query->getGrammar();

        $parentIdName = $grammar->wrap($this->model->getParentIdName());

        $parentAlias = 'p';
        $childAlias = 'c';
        $intermAlias = 'i';

        $waParent = $grammar->wrapTable($parentAlias);
        $waChild = $grammar->wrapTable($childAlias);
        $waInterm = $grammar->wrapTable($intermAlias);

        $query = $this->model
            ->newNestedSetQuery('c')
            ->toBase()
            ->from($this->query->raw("{$table} as {$waChild}, {$table} as {$waParent}, $table as {$waInterm}"))
            ->whereRaw("{$waChild}.{$parentIdName}={$waParent}.{$keyName}")
            ->whereRaw("{$waInterm}.{$keyName} <> {$waParent}.{$keyName}")
            ->whereRaw("{$waInterm}.{$keyName} <> {$waChild}.{$keyName}")
            ->whereNested(function (BaseQueryBuilder $inner) use ($waInterm, $waChild, $waParent) {
                [$lft, $rgt] = $this->wrappedColumns();

                $inner->whereRaw("{$waChild}.{$lft} not between {$waParent}.{$lft} and {$waParent}.{$rgt}")
                    ->orWhereRaw("{$waChild}.{$lft} between {$waInterm}.{$lft} and {$waInterm}.{$rgt}")
                    ->whereRaw("{$waInterm}.{$lft} between {$waParent}.{$lft} and {$waParent}.{$rgt}");
            });

        $this->model->applyNestedSetScope($query, $parentAlias);
        $this->model->applyNestedSetScope($query, $intermAlias);

        return $query;
    }

    /**
     * @return $this
     */
    protected function getMissingParentQuery()
    {
        return $this->model
            ->newNestedSetQuery()
            ->toBase()
            ->whereNested(function (BaseQueryBuilder $inner) {
                $grammar = $this->query->getGrammar();

                $table = $this->wrappedTable();
                $keyName = $this->wrappedKey();
                $parentIdName = $grammar->wrap($this->model->getParentIdName());
                $alias = 'p';
                $wrappedAlias = $grammar->wrapTable($alias);

                $existsCheck = $this->model
                    ->newNestedSetQuery()
                    ->toBase()
                    ->selectRaw('1')
                    ->from($this->query->raw("{$table} as {$wrappedAlias}"))
                    ->whereRaw("{$table}.{$parentIdName} = {$wrappedAlias}.{$keyName}")
                    ->limit(1);

                $this->model->applyNestedSetScope($existsCheck, $alias);

                $inner->whereRaw("{$parentIdName} is not null")
                    ->addWhereExistsQuery($existsCheck, 'and', true);
            });
    }

    /**
     * Get the number of total errors of the tree.
     *
     * @return int
     * @since 2.0
     */
    public function getTotalErrors()
    {
        return array_sum($this->countErrors());
    }

    /**
     * Get whether the tree is broken.
     *
     * @return bool
     * @since 2.0
     */
    public function isBroken()
    {
        return $this->getTotalErrors() > 0;
    }

    /**
     * Fixes the tree based on parentage info.
     *
     * Nodes with invalid parent are saved as roots.
     *
     * @return int The number of fixed nodes
     */
    public function fixTree()
    {
        $columns = [
            $this->model->getKeyName(),
            $this->model->getParentIdName(),
            $this->model->getLftName(),
            $this->model->getRgtName(),
        ];

        $dictionary = $this->model->newNestedSetQuery()
            ->defaultOrder()
            ->get($columns)
            ->groupBy($this->model->getParentIdName())
            ->all();

        return self::fixNodes($dictionary);
    }

    /**
     * @param array $dictionary
     *
     * @return int
     */
    protected static function fixNodes(array &$dictionary)
    {
        $fixed = 0;

        $cut = self::reorderNodes($dictionary, $fixed);

        // Save nodes that have invalid parent as roots
        while (!empty($dictionary)) {
            $dictionary[null] = reset($dictionary);

            unset($dictionary[key($dictionary)]);

            $cut = self::reorderNodes($dictionary, $fixed, null, $cut);
        }

        return $fixed;
    }

    /**
     * @param array $dictionary
     * @param int $fixed
     * @param $parentId
     * @param int $cut
     *
     * @return int
     */
    protected static function reorderNodes(array &$dictionary, &$fixed,
                                           $parentId = null, $cut = 1
    ) {
        if (!isset($dictionary[$parentId])) {
            return $cut;
        }

        /** @var ModelBase|NestedTree $model */
        foreach ($dictionary[$parentId] as $model) {
            $lft = $cut;

            $cut = self::reorderNodes($dictionary,
                $fixed,
                $model->getKey(),
                $cut + 1);

            $rgt = $cut;

            if ($model->rawNode($lft, $rgt, $parentId)->isDirty()) {
                $model->save();

                $fixed++;
            }

            $cut++;
        }

        unset($dictionary[$parentId]);

        return $cut;
    }

    /**
     * Rebuild the tree based on raw data.
     *
     * If item data does not contain primary key, new node will be created.
     *
     * @param array $data
     * @param bool $delete Whether to delete nodes that exists but not in the data
     *                     array
     *
     * @return int
     */
    public function rebuildTree(array $data, $delete = false)
    {
        if ($this->model->usesSoftDelete()) {
            $this->withTrashed();
        }

        $existing = $this->get()->getDictionary();
        $dictionary = [];

        $this->buildRebuildDictionary($dictionary, $data, $existing);

        if (!empty($existing)) {
            if ($delete && !$this->model->usesSoftDelete()) {
                $this->model
                    ->newScopedQuery()
                    ->whereIn($this->model->getKeyName(), array_keys($existing))
                    ->delete();
            }
            else {
                foreach ($existing as $model) {
                    $dictionary[$model->getParentId()][] = $model;

                    if ($delete && $this->model->usesSoftDelete() &&
                        !$model->{$model->getDeletedAtColumn()}
                    ) {
                        $time = $this->model->fromDateTime($this->model->freshTimestamp());

                        $model->{$model->getDeletedAtColumn()} = $time;
                    }
                }
            }
        }

        return $this->fixNodes($dictionary);
    }

    /**
     * @param array $dictionary
     * @param array $data
     * @param array $existing
     * @param mixed $parentId
     */
    protected function buildRebuildDictionary(array &$dictionary,
                                              array $data,
                                              array &$existing,
                                              $parentId = null
    ) {
        $keyName = $this->model->getKeyName();

        foreach ($data as $itemData) {
            if (!isset($itemData[$keyName])) {
                $model = $this->model->newInstance($this->model->getAttributes());

                // We will save it as raw node since tree will be fixed
                $model->rawNode(0, 0, $parentId);
            }
            else {
                if (!isset($existing[$key = $itemData[$keyName]])) {
                    throw new ModelNotFoundException;
                }

                $model = $existing[$key];

                unset($existing[$key]);
            }

            $model->fill(Arr::except($itemData, 'children'))->save();

            $dictionary[$parentId][] = $model;

            if (!isset($itemData['children'])) continue;

            $this->buildRebuildDictionary($dictionary,
                $itemData['children'],
                $existing,
                $model->getKey());
        }
    }

    /**
     * @param string|null $table
     *
     * @return $this
     */
    public function applyNestedSetScope($table = null)
    {
        return $this->model->applyNestedSetScope($this, $table);
    }

    /**
     * Get the root node.
     *
     * @param array $columns
     *
     * @return self
     */
    public function root(array $columns = ['*'])
    {
        return $this->whereIsRoot()->first($columns);
    }
}
