<?php

namespace Igniter\Flame\Database\Traits;

use Exception;

/**
 * Sortable model trait
 *
 * Usage:
 *
 * Model table must have priority table column.
 *
 * In the model class definition:
 *
 *   use \Igniter\Flame\Database\Traits\Sortable;
 *
 * To set orders:
 *
 *   $model->setSortableOrder($recordIds, $recordOrders);
 *
 * You can change the sort field used by declaring:
 *
 *   public $sortable [
 *      'sortOrderColumn' = 'priority',
 *      'sortWhenCreating' = FALSE,
 *  ];
 *
 */
trait Sortable
{
    /**
     * Boot the sortable trait for this model.
     *
     * @return void
     */
    public static function bootSortable()
    {
        static::creating(function ($model) {
            $sortOrderColumn = $model->getSortOrderColumn();

            // only automatically calculate next position with max+1 when a position has not been set already
            if ($model->sortWhenCreating() AND $sortOrderColumn) {
                $model->setAttribute($sortOrderColumn, static::on()->max($sortOrderColumn) + 1);
            }
        });
    }

    public function scopeSorted($query)
    {
        return $query->orderBy($this->getSortOrderColumn());
    }

    /**
     * Sets the sort order of records to the specified orders. If the orders is
     * undefined, the record identifier is used.
     *
     * @param $itemIds
     * @param null $itemOrders
     *
     * @throws \Exception
     */
    public function setSortableOrder($itemIds, $itemOrders = null)
    {
        if (!is_array($itemIds))
            $itemIds = [$itemIds];

        if ($itemOrders === null)
            $itemOrders = $itemIds;

        if (count($itemIds) != count($itemOrders)) {
            throw new Exception('Invalid setSortableOrder call - count of itemIds do not match count of itemOrders');
        }

        foreach ($itemIds as $index => $id) {
            $order = $itemOrders[$index];
            $this->newQuery()->where('id', $id)->update([$this->getSortOrderColumn() => $order]);
        }
    }

    /**
     * Get the name of the "sort order" column.
     *
     * @return string
     */
    public function getSortOrderColumn()
    {
        return $this->sortable['sortOrderColumn'] ?? static::SORT_ORDER;
    }

    public function sortWhenCreating()
    {
        return $this->sortable['sortWhenCreating'] ?? true;
    }
}