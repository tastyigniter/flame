<?php

namespace Igniter\Flame\Database;

use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Igniter\Flame\Database\Query\Builder as QueryBuilder;
use Igniter\Flame\Traits\EventEmitter;
use Igniter\Flame\Traits\ExtendableTrait;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Model Class
 * @package        Igniter\Flame\Database\Model.php
 */
class Model extends EloquentModel
{
    use ExtendableTrait;
    use EventEmitter;

    /**
     * @var array Behaviors implemented by this model.
     */
    public $implement;

    /**
     * @var array Make the model's attributes public so actions can modify them.
     */
    public $attributes = [];

    public $timestamps = FALSE;

    /**
     * The storage format of the model's time columns.
     * @var string
     */
    protected $timeFormat;

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
    ];

    /**
     * @var array Excepted relationship types, used to cycle and verify relationships.
     */
    protected static $relationTypes = ['hasOne', 'hasMany', 'belongsTo', 'belongsToMany', 'morphTo', 'morphOne',
        'morphMany', 'morphToMany', 'morphedByMany', 'hasManyThrough'];

    /**
     * The attributes that should be cast to native types.
     * New Custom types: serialize, time
     * @var array
     */
    public $casts = [];

    /**
     * @var array The array of models booted events.
     */
    protected static $eventsBooted = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->bootNicerEvents();
        $this->extendableConstruct();
        $this->fill($attributes);
    }

    /**
     * Create a new model and return the instance.
     *
     * @param array $attributes
     *
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public static function make($attributes = [])
    {
        return new static($attributes);
    }

    /**
     * Save a new model and return the instance.
     *
     * @param array $attributes
     * @param string $sessionKey
     *
     * @return \Illuminate\Database\Eloquent\Model|static
     * @throws \Exception
     */
    public static function create(array $attributes = [], $sessionKey = null)
    {
        $model = new static($attributes);
        $model->save(null, $sessionKey);

        return $model;
    }

    /**
     * Reloads the model attributes from the database.
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function reload()
    {
        if (!$this->exists) {
            $this->syncOriginal();
        }
        elseif ($fresh = static::find($this->getKey())) {
            $this->setRawAttributes($fresh->getAttributes(), TRUE);
        }

        return $this;
    }

    /**
     * Reloads the model relationship cache.
     *
     * @param string $relationName
     *
     * @return void
     */
    public function reloadRelations($relationName = null)
    {
        if (!$relationName) {
            $this->setRelations([]);
        }
        else {
            unset($this->relations[$relationName]);
        }
    }

    /**
     * Extend this object properties upon construction.
     *
     * @param \Closure $callback
     */
    public static function extend(Closure $callback)
    {
        self::extendableExtendCallback($callback);
    }

    /**
     * Bind some nicer events to this model, in the format of method overrides.
     */
    protected function bootNicerEvents()
    {
        $class = get_called_class();

        if (isset(static::$eventsBooted[$class])) {
            return;
        }

        $radicals = ['creat', 'sav', 'updat', 'delet', 'fetch'];
        $hooks = ['before' => 'ing', 'after' => 'ed'];

        foreach ($radicals as $radical) {
            foreach ($hooks as $hook => $event) {

                $eventMethod = $radical.$event; // saving / saved
                $method = $hook.ucfirst($radical); // beforeSave / afterSave
                if ($radical != 'fetch') $method .= 'e';

                self::$eventMethod(function (Model $model) use ($method) {
                    $model->fireEvent('model.'.$method);

                    if ($model->methodExists($method))
                        return $model->$method();
                });
            }
        }

        // Hook to boot events
        static::registerModelEvent('booted', function (Model $model) {
            $model->fireEvent('model.afterBoot');
            if ($model->methodExists('afterBoot'))
                return $model->afterBoot();
        });

        static::$eventsBooted[$class] = TRUE;
    }

    /**
     * Remove all of the event listeners for the model
     * Also flush registry of models that had events booted
     * Allows painless unit testing.
     * @override
     * @return void
     */
    public static function flushEventListeners()
    {
        parent::flushEventListeners();
        static::$eventsBooted = [];
    }

    /**
     * Create a new model instance that is existing.
     *
     * @param array $attributes
     *
     * @param null $connection
     *
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $instance = $this->newInstance([], TRUE);
        if ($instance->fireModelEvent('fetching') === FALSE)
            return $instance;

        $instance->setRawAttributes((array)$attributes, TRUE);

        $instance->fireModelEvent('fetched', FALSE);

        $instance->setConnection($connection ?: $this->connection);

        return $instance;
    }

    /**
     * Handle the "creating" model event
     */
    protected function beforeCreate()
    {
    }

    /**
     * Handle the "created" model event
     */
    protected function afterCreate()
    {
    }

    /**
     * Handle the "updating" model event
     */
    protected function beforeUpdate()
    {
    }

    /**
     * Handle the "updated" model event
     */
    protected function afterUpdate()
    {
    }

    /**
     * Handle the "saving" model event
     */
    protected function beforeSave()
    {
    }

    /**
     * Handle the "saved" model event
     */
    protected function afterSave()
    {
    }

    /**
     * Handle the "deleting" model event
     */
    protected function beforeDelete()
    {
    }

    /**
     * Handle the "deleted" model event
     */
    protected function afterDelete()
    {
    }

    /**
     * Handle the "fetching" model event
     */
    protected function beforeFetch()
    {
    }

    /**
     * Handle the "fetched" model event
     */
    protected function afterFetch()
    {
    }

    /**
     * Create a new native event for handling beforeFetch().
     *
     * @param Closure|string $callback
     *
     * @return void
     */
    public static function fetching($callback)
    {
        static::registerModelEvent('fetching', $callback);
    }

    /**
     * Create a new native event for handling afterFetch().
     *
     * @param Closure|string $callback
     *
     * @return void
     */
    public static function fetched($callback)
    {
        static::registerModelEvent('fetched', $callback);
    }

    public function setUpdatedAt($value)
    {
        if (!is_null(static::UPDATED_AT)) $this->{static::UPDATED_AT} = $value;

        return $this;
    }

    public function setCreatedAt($value)
    {
        if (!is_null(static::CREATED_AT)) $this->{static::CREATED_AT} = $value;

        return $this;
    }

    //
    // Overrides
    //

    /**
     * Get the observable event names.
     * @return array
     */
    public function getObservableEvents()
    {
        return array_merge(
            [
                'creating', 'created', 'updating', 'updated',
                'deleting', 'deleted', 'saving', 'saved',
                'restoring', 'restored', 'fetching', 'fetched',
            ],
            $this->observables
        );
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param array $attributes
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes)
    {
        return parent::fill($attributes);
    }

    /**
     * Cast an attribute to a native PHP type.
     * Cast an attribute to a native PHP type.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if (is_null($value)) {
            return $value;
        }

        switch ($this->getCastType($key)) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'real':
            case 'float':
            case 'double':
                return (float)$value;
            case 'string':
                return (string)$value;
            case 'bool':
            case 'boolean':
                return (bool)$value;
            case 'object':
                return $this->fromJson($value, TRUE);
            case 'array':
            case 'json':
                return $this->fromJson($value);
            case 'collection':
                return new EloquentCollection($this->fromJson($value));
            case 'date':
                return $this->asDate($value);
            case 'datetime':
                return $this->asDateTime($value);
            case 'timestamp':
                return $this->asTimeStamp($value);
//            case 'time':
//                return $this->asTime($value);
            case 'serialize':
                return $this->fromSerialized($value);
            default:
                return $value;
        }
    }

    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes) || $this->hasGetMutator($key)) {
            return $this->getAttributeValue($key);
        }

        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        if ($this->hasRelation($key) || method_exists($this, $key)) {
            return $this->getRelationshipFromMethod($key);
        }
    }

    /**
     * Set a given attribute on the model.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return self
     */
    public function setAttribute($key, $value)
    {
        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // the model, such as "json_encoding" an listing of data for storage.
        if ($this->hasSetMutator($key)) {
            $method = 'set'.Str::studly($key).'Attribute';

            return $this->{$method}($value);
        }

        // If an attribute is listed as a "date", we'll convert it from a DateTime
        // instance into a form proper for storage on the database tables using
        // the connection grammar's date format. We will auto set the values.
        elseif ($value && (in_array($key, $this->getDates()) || $this->isDateCastable($key))) {
            $value = $this->fromDateTime($value);
        }

        if (!is_null($value) && $this->isSerializedCastable($key)) {
            $value = $this->asSerialized($value);
        }

        if ($this->isJsonCastable($key) && !is_null($value)) {
            $value = $this->asJson($value);
        }

        // If this attribute contains a JSON ->, we'll set the proper value in the
        // attribute's underlying array. This takes care of properly nesting an
        // attribute in the array's value in the case of deeply nested items.
        if (Str::contains($key, '->')) {
            return $this->fillJsonAttribute($key, $value);
        }

        $this->attributes[$key] = $value;

        $this->fireEvent('model.setAttribute', [$key, $value]);

        return $this;
    }

    protected function asSerialized($value)
    {
        return isset($value) ? serialize($value) : null;
    }

    public function fromSerialized($value)
    {
        return isset($value) ? @unserialize($value) : null;
    }

    protected function isSerializedCastable($key)
    {
        return $this->hasCast($key, ['serialize']);
    }

    protected function asDateTime($value)
    {
        try {
            $value = parent::asDateTime($value);
        }
        catch (InvalidArgumentException $ex) {
            $value = Carbon::parse($value);
        }

        return $value;
    }

    protected function asTime($value)
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($value instanceof Carbon) {
            return $value;
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof DateTimeInterface) {
            return new Carbon(
                $value->format('H:i:s.u'), $value->getTimezone()
            );
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your time fields as they might be UNIX timestamps here.
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        }

        // If the value is in simply hour, minute, second format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple time
        // fields on the database, while still supporting Carbonized conversion.
        if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $value)) {
            return Carbon::createFromFormat('H:i:s', $value);
        }

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        return Carbon::createFromFormat($this->getTimeFormat(), $value);
    }

    /**
     * Convert a Carbon Time to a storable string.
     *
     * @param \Carbon\Carbon|int $value
     *
     * @return string
     */
    public function fromTime($value)
    {
//        if ($value == '00:00' OR $value == '00:00:00')
//            return $value;
//
        $format = $this->getTimeFormat();

        return $this->asTime($value)->format($format);
    }

    /**
     * Determine whether a value is Time castable for inbound manipulation.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function isTimeCastable($key)
    {
        return $this->hasCast($key, ['timee']);
    }

    /**
     * Get the format for database stored times.
     * @return string
     */
    protected function getTimeFormat()
    {
        return $this->timeFormat ?: 'H:i:s';
    }

    /**
     * Set the time format used by the model.
     *
     * @param string $format
     *
     * @return self
     */
    public function setTimeFormat($format)
    {
        $this->timeFormat = $format;

        return $this;
    }

    /**
     * Determine if the model or given attribute(s) have been modified.
     *
     * @param array|string|null $attributes
     *
     * @return bool
     */
    public function isDirty($attributes = null)
    {
        $dirty = $this->getDirty();

        if (is_null($attributes)) {
            return count($dirty) > 0;
        }

        if (!is_array($attributes)) {
            $attributes = func_get_args();
        }

        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $dirty) AND !is_null($dirty[$attribute])) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return \Igniter\Flame\Database\Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
     * @return \Igniter\Flame\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new QueryBuilder(
            $connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
        );
    }

    /**
     * Get the default foreign key name for the model.
     * @return string
     */
    public function getForeignKey()
    {
        return Str::snake(Str::singular(str_replace('_model', '', class_basename($this)))).'_id';
    }

    /**
     * __get magic
     * Allows models to access CI's loaded classes using the same
     * syntax as controllers.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->extendableGet($key);
    }

    public function __set($name, $value)
    {
        return $this->extendableSet($name, $value);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param string $method
     * @param $params
     *
     * @return mixed
     */
    public function __call($method, $params)
    {
        if ($this->hasRelation($method))
            return $this->handleRelation($method);

        return $this->extendableCall($method, $params);
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    public function hasRelation($name)
    {
        return $this->getRelationDefinition($name) !== null ? TRUE : FALSE;
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
     * @return boolean
     */
    public function isRelationPushable($name)
    {
        $definition = $this->getRelationDefinition($name);
        if (is_null($definition) || !array_key_exists('push', $definition)) {
            return TRUE;
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
                return ['order' => 'sort_order', 'delete' => TRUE];

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
                $relation = $this->validateRelationArgs($relationName,
                    ['foreignKey', 'otherKey']
                );
                $relationObj = $this->$relationType(
                    $relation[0],
                    $relation['foreignKey'],
                    $relation['otherKey'],
                    $relationName);
                break;

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
                    $relation['foreignKey'], $relationName);
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
                    $relation['otherKey'], null, FALSE);
                break;

            case 'morphedByMany':
                $relation = $this->validateRelationArgs($relationName,
                    ['table', 'foreignKey', 'otherKey', 'pivot', 'timestamps'], ['name']
                );
                $relationObj = $this->$relationType(
                    $relation[0],
                    $relation['name'],
                    $relation['table'],
                    $relation['foreignKey'],
                    $relation['otherKey'], $relationName);
                break;

            case 'hasManyThrough':
                $relation = $this->validateRelationArgs($relationName, ['foreignKey', 'throughKey', 'otherKey'], ['through']);
                $relationObj = $this->$relationType(
                    $relation[0],
                    $relation['through'],
                    $relation['foreignKey'],
                    $relation['throughKey'],
                    $relation['otherKey']);
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

    public function belongsTo($related, $foreignKey = null, $otherKey = null, $relation = null)
    {
        if (is_null($relation)) {
            $relation = $this->guessBelongsToRelation();
        }

        $instance = $this->newRelatedInstance($related);

        if (is_null($foreignKey)) {
            $foreignKey = snake_case($relation).'_id';
        }

        $otherKey = $otherKey ?: $instance->getKeyName();

        return new BelongsTo(
            $instance->newQuery(), $this, $foreignKey, $otherKey, $relation
        );
    }

    public function belongsToMany($related,
                                  $table = null, $foreignPivotKey = null, $relatedPivotKey = null,
                                  $parentKey = null, $relatedKey = null, $relation = null)
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if (is_null($relation)) {
            $relation = $this->guessBelongsToManyRelation();
        }

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if (is_null($table)) {
            $table = $this->joiningTable($related);
        }

        return new BelongsToMany(
            $instance->newQuery(), $this, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(), $relation
        );
    }

    //
    // Saving
    //

    /**
     * Save the model to the database. Is used by {@link save()}
     *
     * @param array $options
     *
     * @return bool
     * @throws \Exception
     */
    protected function saveInternal($options = [])
    {
        // Event
        if ($this->fireEvent('model.saveInternal', [$this->attributes, $options], TRUE) === FALSE) {
            return FALSE;
        }

        // Save the record
        $result = parent::save($options);

        // Halted by event
        if ($result === FALSE) {
            return $result;
        }

        //If there is nothing to update, Eloquent will not fire afterSave(),
        // events should still fire for consistency.
        if ($result === null) {
            $this->fireModelEvent('updated', FALSE);
            $this->fireModelEvent('saved', FALSE);
        }

        return $result;
    }

    /**
     * Save the model to the database.
     *
     * @param array $options
     * @param null $sessionKey
     *
     * @return bool
     * @throws \Exception
     */
    public function save(array $options = null, $sessionKey = null)
    {
        return $this->saveInternal(['force' => FALSE] + (array)$options);
    }

    /**
     * Save the model and all of its relationships.
     *
     * @param array $options
     * @param null $sessionKey
     *
     * @return bool
     * @throws \Exception
     */
    public function push($options = null, $sessionKey = null)
    {
        $always = Arr::get($options, 'always', FALSE);

        if (!$this->save(null, $sessionKey) && !$always) {
            return FALSE;
        }

        foreach ($this->relations as $name => $models) {
            if (!$this->isRelationPushable($name)) {
                continue;
            }

            if ($models instanceof EloquentCollection) {
                $models = $models->all();
            }
            elseif ($models instanceof EloquentModel) {
                $models = [$models];
            }
            else {
                $models = (array)$models;
            }

            foreach (array_filter($models) as $model) {
                if (!$model->push(null, $sessionKey)) {
                    return FALSE;
                }
            }
        }

        return TRUE;
    }

    /**
     * Pushes the first level of relations even if the parent
     * model has no changes.
     *
     * @param array $options
     * @param string $sessionKey
     *
     * @return bool
     */
    public function alwaysPush($options = null, $sessionKey)
    {
        return $this->push(['always' => TRUE] + (array)$options, $sessionKey);
    }

    /**
     * Perform the actual delete query on this model instance.
     * @return void
     */
    protected function performDeleteOnModel()
    {
        $this->performDeleteOnRelations();
        $this->setKeysForSaveQuery($this->newQueryWithoutScopes())->delete();
    }

    /**
     * Locates relations with delete flag and cascades the delete event.
     * @return void
     */
    protected function performDeleteOnRelations()
    {
        $definitions = $this->getRelationDefinitions();
        foreach ($definitions as $type => $relations) {
            /*
             * Hard 'delete' definition
             */
            foreach ($relations as $name => $options) {
                if (!Arr::get($options, 'delete', FALSE)) {
                    continue;
                }

                if (!$relation = $this->{$name}) {
                    continue;
                }

                if ($relation instanceof EloquentModel) {
                    $relation->forceDelete();
                }
                elseif ($relation instanceof EloquentCollection) {
                    $relation->each(function ($model) {
                        $model->forceDelete();
                    });
                }
            }

            /*
             * Belongs-To-Many should clean up after itself always
             */
            if ($type == 'belongsToMany') {
                foreach ($relations as $name => $options) {
                    if (!Arr::get($options, 'delete', FALSE)) {
                        continue;
                    }

                    $this->{$name}()->detach();
                }
            }
        }
    }
}
