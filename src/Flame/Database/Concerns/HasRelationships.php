<?php

namespace Igniter\Flame\Database\Concerns;

use Igniter\Flame\Database\Relations\BelongsTo;
use Igniter\Flame\Database\Relations\BelongsToMany;
use Igniter\Flame\Database\Relations\HasMany;
use Igniter\Flame\Database\Relations\HasManyThrough;
use Igniter\Flame\Database\Relations\HasOne;
use Igniter\Flame\Database\Relations\HasOneThrough;
use Igniter\Flame\Database\Relations\MorphMany;
use Igniter\Flame\Database\Relations\MorphOne;
use Igniter\Flame\Database\Relations\MorphTo;
use Igniter\Flame\Database\Relations\MorphToMany;
use Illuminate\Support\Str;
use InvalidArgumentException;

trait HasRelationships
{
    /**
     * The loaded relationships for the model.
     * It should be declared with keys as the relation name, and value being a mixed array.
     * The relation type $morphTo does not include a classname as the first value.
     * ex:
     * 1. string $table_name table name value mode, model_name, foreign key is auto-generated,
     * by appending _id to the singular table_name
     * $hasOne = [$relation => $model) associative array mode
     * $hasMany = [$relation => [$model]] associative array mode
     * $belongsTo = [$relation, [$model, 'foreignKey' => $foreignKey]] custom key/value mode
     * $hasMany = [$relation, [$model, 'foreignKey' => $foreignKey, 'otherKey' => $otherKey]] custom key/value mode
     * $belongsToMany = [$relation, [$model, 'foreignKey' => $foreignKey, 'otherKey' => $otherKey]] custom key/value
     * mode
     * $morphOne = [$relation, [$model, 'name' => 'name']] custom key/value mode
     * $morphMany = [$relation, [$model, 'table' => 'table_name', 'name' => 'name']] custom key/value mode
     */
    public $relation = [
        'hasMany' => [],
        'hasOne' => [],
        'belongsTo' => [],
        'belongsToMany' => [],
        'morphTo' => [],
        'morphOne' => [],
        'morphMany' => [],
        'morphToMany' => [],
        'morphedByMany' => [],
        'hasManyThrough' => [],
        'hasOneThrough' => [],
    ];

    /**
     * @var array Excepted relationship types, used to cycle and verify relationships.
     */
    protected static $relationTypes = [
        'hasOne', 'hasMany', 'belongsTo', 'belongsToMany', 'morphTo', 'morphOne',
        'morphMany', 'morphToMany', 'morphedByMany', 'hasManyThrough', 'hasOneThrough',
    ];

    public function hasRelation($name)
    {
        return $this->getRelationDefinition($name) !== null;
    }

    /**
     * Returns relationship details from a supplied name.
     *
     * @param string $name Relation name
     *
     * @return array
     */
    public function getRelationDefinition($name)
    {
        if (($type = $this->getRelationType($name)) !== null) {
            return (array)$this->relation[$type][$name] + $this->getRelationDefaults($type);
        }
    }

    /**
     * Returns relationship details for all relations defined on this model.
     * @return array
     */
    public function getRelationDefinitions()
    {
        $result = [];

        foreach (static::$relationTypes as $type) {
            if (!isset($this->relation[$type])) continue;

            $result[$type] = $this->relation[$type];

            // Apply default values for the relation type
            if ($defaults = $this->getRelationDefaults($type)) {
                foreach ($result[$type] as $relation => $options) {
                    $result[$type][$relation] = (array)$options + $defaults;
                }
            }
        }

        return $result;
    }

    /**
     * Returns a relationship type based on a supplied name.
     *
     * @param string $name Relation name
     *
     * @return string
     */
    public function getRelationType($name)
    {
        foreach (static::$relationTypes as $type) {
            if (isset($this->relation[$type][$name])) {
                return $type;
            }
        }
    }

    /**
     * Get a relationship.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getRelationValue($key)
    {
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        if ($this->hasRelation($key)) {
            return $this->getRelationshipFromMethod($key);
        }
    }

    /**
     * Sets a relation value directly from its attribute.
     * @param $relationName
     * @param $value
     * @return $this
     */
    protected function setRelationValue($relationName, $value)
    {
        $this->$relationName()->setSimpleValue($value);

        return $this;
    }

    /**
     * Returns a relation class object
     *
     * @param string $name Relation name
     *
     * @return string
     */
    public function makeRelation($name)
    {
        $relationType = $this->getRelationType($name);
        $relation = $this->getRelationDefinition($name);

        if ($relationType == 'morphTo' || !isset($relation[0])) {
            return null;
        }

        $relationClass = $relation[0];

        return new $relationClass();
    }

    /**
     * Determines whether the specified relation should be saved
     * when push() is called instead of save() on the model. Default: true.
     *
     * @param string $name Relation name
     *
     * @return bool
     */
    public function isRelationPushable($name)
    {
        $definition = $this->getRelationDefinition($name);
        if (is_null($definition) || !array_key_exists('push', $definition)) {
            return true;
        }

        return (bool)$definition['push'];
    }

    /**
     * Returns default relation arguments for a given type.
     *
     * @param string $type Relation type
     *
     * @return array
     */
    protected function getRelationDefaults($type)
    {
        switch ($type) {
            case 'attachOne':
            case 'attachMany':
                return ['order' => 'sort_order', 'delete' => true];

            default:
                return [];
        }
    }

    public function handleRelation($relationName)
    {
        $relationType = $this->getRelationType($relationName);
        $relation = $this->getRelationDefinition($relationName);

        if (!isset($relation[0]) && $relationType != 'morphTo')
            throw new InvalidArgumentException(sprintf(
                "Relation '%s' on model '%s' should have at least a classname.", $relationName, get_called_class()
            ));

        if (isset($relation[0]) && $relationType == 'morphTo')
            throw new InvalidArgumentException(sprintf(
                "Relation '%s' on model '%s' is a morphTo relation and should not contain additional arguments.", $relationName, get_called_class()
            ));

        switch ($relationType) {
            case 'hasOne':
            case 'hasMany':
            case 'belongsTo':
                $relation = $this->validateRelationArgs($relationName,
                    ['foreignKey', 'otherKey']
                );
                $relationObj = $this->$relationType(
                    $relation[0],
                    $relation['foreignKey'],
                    $relation['otherKey'],
                    $relationName);
                break;

            case 'belongsToMany':
                $relation = $this->validateRelationArgs($relationName,
                    ['table', 'foreignKey', 'otherKey', 'parentKey', 'relatedKey', 'pivot', 'timestamps']
                );

                $relationObj = $this->$relationType(
                    $relation[0],
                    $relation['table'],
                    $relation['foreignKey'],
                    $relation['otherKey'],
                    $relation['parentKey'],
                    $relation['relatedKey'],
                    $relationName);
                break;

            case 'morphTo':
                $relation = $this->validateRelationArgs($relationName,
                    ['name', 'type', 'id']
                );
                $relationObj = $this->$relationType($relation['name'] ?: $relationName, $relation['type'], $relation['id']);
                break;

            case 'morphOne':
            case 'morphMany':
                $relation = $this->validateRelationArgs($relationName,
                    ['type', 'id', 'foreignKey'], ['name']
                );
                $relationObj = $this->$relationType(
                    $relation[0],
                    $relation['name'],
                    $relation['type'],
                    $relation['id'],
                    $relation['foreignKey'],
                    $relationName
                );
                break;

            case 'morphToMany':
                $relation = $this->validateRelationArgs($relationName,
                    ['table', 'foreignKey', 'otherKey', 'pivot', 'timestamps'], ['name']
                );
                $relationObj = $this->$relationType(
                    $relation[0],
                    $relation['name'],
                    $relation['table'],
                    $relation['pivot'],
                    $relation['foreignKey'],
                    $relation['otherKey'],
                    null,
                    false,
                    $relationName);
                break;

            case 'morphedByMany':
                $relation = $this->validateRelationArgs($relationName,
                    ['table', 'foreignKey', 'otherKey', 'parentKey', 'relatedKey', 'pivot', 'timestamps'], ['name']
                );
                $relationObj = $this->$relationType(
                    $relation[0],
                    $relation['name'],
                    $relation['table'],
                    $relation['foreignKey'],
                    $relation['otherKey'],
                    $relation['parentKey'],
                    $relation['relatedKey'],
                    $relationName
                );
                break;

            case 'hasOneThrough':
            case 'hasManyThrough':
                $relation = $this->validateRelationArgs($relationName, ['foreignKey', 'throughKey', 'otherKey', 'secondOtherKey'], ['through']);
                $relationObj = $this->$relationType(
                    $relation[0],
                    $relation['through'],
                    $relation['foreignKey'],
                    $relation['throughKey'],
                    $relation['otherKey'],
                    $relation['secondOtherKey'],
                    $relationName
                );
                break;

            default:
                throw new InvalidArgumentException(sprintf("There is no such relation type known as '%s' on model '%s'.", $relationType, get_called_class()));
        }

        return $relationObj;
    }

    /**
     * Validate relation supplied arguments.
     *
     * @param $relationName
     * @param $optional
     * @param array $required
     *
     * @return array
     */
    protected function validateRelationArgs($relationName, $optional, $required = [])
    {
        $relation = $this->getRelationDefinition($relationName);

        // Query filter arguments
        $filters = ['scope', 'conditions', 'order', 'pivot', 'timestamps', 'push', 'count'];

        foreach (array_merge($optional, $filters) as $key) {
            if (!array_key_exists($key, $relation)) {
                $relation[$key] = null;
            }
        }

        $missingRequired = [];
        foreach ($required as $key) {
            if (!array_key_exists($key, $relation)) {
                $missingRequired[] = $key;
            }
        }

        if ($missingRequired) {
            throw new InvalidArgumentException(sprintf('Relation "%s" on model "%s" should contain the following key(s): %s',
                $relationName,
                get_called_class(),
                implode(', ', $missingRequired)
            ));
        }

        return $relation;
    }

    /**
     * Define a one-to-one relationship.
     *
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @param null $relationName
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function hasOne($related, $foreignKey = null, $localKey = null, $relationName = null)
    {
        $relationName = $relationName ?: $this->guessBelongsToRelation();

        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasOne(
            $instance->newQuery(),
            $this,
            $instance->getTable().'.'.$foreignKey,
            $localKey,
            $relationName
        );
    }

    /**
     * Define a has-one-through relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \Igniter\Flame\Database\Relations\HasOneThrough
     */
    public function hasOneThrough($related, $through, $primaryKey = null, $throughKey = null, $localKey = null, $secondLocalKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $throughInstance = new $through;

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $throughKey = $throughKey ?: $throughInstance->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        $secondLocalKey = $secondLocalKey ?: $throughInstance->getKeyName();

        $instance = $this->newRelatedInstance($related);

        return new HasOneThrough($instance->newQuery(), $this, $throughInstance, $primaryKey, $throughKey, $localKey, $secondLocalKey, $relationName);
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @param null $relationName
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null, $relationName = null)
    {
        $relationName = $relationName ?: $this->guessBelongsToRelation();

        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasMany(
            $instance->newQuery(),
            $this,
            $instance->getTable().'.'.$foreignKey,
            $localKey,
            $relationName
        );
    }

    /**
     * Define a has-many-through relationship.
     *
     * @param string $related
     * @param string $through
     * @param string|null $firstKey
     * @param string|null $secondKey
     * @param string|null $localKey
     * @param string|null $secondLocalKey
     * @param null $relationName
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function hasManyThrough($related, $through, $firstKey = null, $secondKey = null, $localKey = null, $secondLocalKey = null, $relationName = null)
    {
        $relationName = $relationName ?: $this->guessBelongsToRelation();

        $through = new $through;

        $firstKey = $firstKey ?: $this->getForeignKey();

        $secondKey = $secondKey ?: $through->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        $secondLocalKey = $secondLocalKey ?: $through->getKeyName();

        $instance = $this->newRelatedInstance($related);

        return new HasManyThrough(
            $instance->newQuery(),
            $this,
            $through,
            $firstKey,
            $secondKey,
            $localKey,
            $secondLocalKey,
            $relationName
        );
    }

    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relationName = null)
    {
        $relationName = $relationName ?: $this->guessBelongsToRelation();

        $instance = $this->newRelatedInstance($related);

        if (is_null($foreignKey)) {
            $foreignKey = snake_case($relationName).'_id';
        }

        $otherKey = $ownerKey ?: $instance->getKeyName();

        return new BelongsTo(
            $instance->newQuery(),
            $this,
            $foreignKey,
            $otherKey,
            $relationName
        );
    }

    public function belongsToMany(
        $related,
        $table = null, $foreignPivotKey = null, $relatedPivotKey = null,
        $parentKey = null, $relatedKey = null, $relationName = null
    ) {
        $relationName = $relationName ?: $this->guessBelongsToRelation();

        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        $table = $table ?: $this->joiningTable($related);

        return new BelongsToMany(
            $instance->newQuery(), $this, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(), $relationName
        );
    }

    /**
     * Define a polymorphic one-to-one relationship.
     *
     * @param string $related
     * @param string $name
     * @param string|null $type
     * @param string|null $id
     * @param string|null $localKey
     * @param null $relationName
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function morphOne($related, $name, $type = null, $id = null, $localKey = null, $relationName = null)
    {
        $relationName = $relationName ?: $this->guessBelongsToRelation();

        $instance = $this->newRelatedInstance($related);

        [$type, $id] = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return new MorphOne(
            $instance->newQuery(),
            $this,
            $table.'.'.$type,
            $table.'.'.$id,
            $localKey,
            $relationName
        );
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param string $name
     * @param string $type
     * @param string $id
     * @param $ownerKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    protected function morphEagerTo($name, $type, $id, $ownerKey)
    {
        return new MorphTo(
            $this->newQuery()->setEagerLoads([]),
            $this,
            $id,
            $ownerKey,
            $type,
            $name
        );
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param string $target
     * @param string $name
     * @param string $type
     * @param string $id
     * @param $ownerKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    protected function morphInstanceTo($target, $name, $type, $id, $ownerKey)
    {
        $instance = $this->newRelatedInstance(
            static::getActualClassNameForMorph($target)
        );

        return new MorphTo(
            $instance->newQuery(),
            $this,
            $id,
            $ownerKey ?? $instance->getKeyName(),
            $type,
            $name
        );
    }

    /**
     * Define a polymorphic one-to-many relationship.
     *
     * @param string $related
     * @param string $name
     * @param string|null $type
     * @param string|null $id
     * @param string|null $localKey
     * @param null $relationName
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function morphMany($related, $name, $type = null, $id = null, $localKey = null, $relationName = null)
    {
        $relationName = $relationName ?: $this->guessBelongsToRelation();

        $instance = $this->newRelatedInstance($related);

        [$type, $id] = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return new MorphMany(
            $instance->newQuery(),
            $this,
            $table.'.'.$type,
            $table.'.'.$id,
            $localKey,
            $relationName
        );
    }

    /**
     * Define a polymorphic many-to-many relationship.
     *
     * @param string $related
     * @param string $name
     * @param string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string|null $parentKey
     * @param string|null $relatedKey
     * @param bool $inverse
     * @param null $relationName
     * @return \Igniter\Flame\Database\Relations\MorphToMany
     */
    public function morphToMany(
        $related, $name, $table = null, $foreignPivotKey = null,
        $relatedPivotKey = null, $parentKey = null,
        $relatedKey = null, $inverse = false, $relationName = null
    ) {
        $relationName = $relationName ?: $this->guessBelongsToManyRelation();

        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $name.'_id';

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        if (!$table) {
            $words = preg_split('/(_)/u', $name, -1, PREG_SPLIT_DELIM_CAPTURE);

            $lastWord = array_pop($words);

            $table = implode('', $words).Str::plural($lastWord);
        }

        return new MorphToMany(
            $instance->newQuery(),
            $this,
            $name,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            $relationName,
            $inverse
        );
    }

    /**
     * Define a polymorphic, inverse many-to-many relationship.
     *
     * @param string $related
     * @param string $name
     * @param string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string|null $parentKey
     * @param string|null $relatedKey
     * @param null $relationName
     * @return \Igniter\Flame\Database\Relations\MorphToMany
     */
    public function morphedByMany(
        $related, $name, $table = null, $foreignPivotKey = null,
        $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $relationName = null
    ) {
        $relationName = $relationName ?: $this->guessBelongsToRelation();

        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        $relatedPivotKey = $relatedPivotKey ?: $name.'_id';

        return $this->morphToMany(
            $related,
            $name,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            true,
            $relationName
        );
    }
}
