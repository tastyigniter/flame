<?php

namespace Igniter\Flame\Database\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough as HasManyThroughBase;

/**
 * Adapted from october\rain\database\relations\HasManyThrough
 */
class HasManyThrough extends HasManyThroughBase
{
    use DefinedConstraints;

    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    /**
     * Create a new has many relationship instance.
     * @return void
     */
    public function __construct(Builder $query, Model $farParent, Model $parent, $firstKey, $secondKey, $localKey, $secondLocalKey, $relationName = null)
    {
        $this->relationName = $relationName;

        parent::__construct($query, $farParent, $parent, $firstKey, $secondKey, $localKey, $secondLocalKey);

        $this->addDefinedConstraints();
    }

    /**
     * Determine whether close parent of the relation uses Soft Deletes.
     *
     * @return bool
     */
    public function parentSoftDeletes()
    {
        $uses = class_uses_recursive(get_class($this->parent));

        return in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, $uses);
    }
}
