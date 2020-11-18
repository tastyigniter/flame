<?php

namespace Igniter\Flame\Database;

use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Exception;
use Igniter\Flame\Database\Query\Builder as QueryBuilder;
use Igniter\Flame\Traits\EventEmitter;
use Igniter\Flame\Traits\ExtendableTrait;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Model Class
 */
class Model extends EloquentModel
{
    use ExtendableTrait;
    use EventEmitter;
    use Concerns\HasRelationships;

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

    public function getAttributeValue($key)
    {
        $attr = parent::getAttributeValue($key);

        if ($this->isSerializedCastable($key) AND !empty($attr) AND is_string($attr)) {
            $attr = $this->fromSerialized($attr);
        }

        return $attr;
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
        if (empty($key)) {
            throw new Exception('Cannot access empty model attribute.');
        }

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

        if ($this->hasRelation($key) AND !$this->isRelationPurgeable($key)) {
            return $this->setRelationValue($key, $value);
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

    protected function isRelationPurgeable($name)
    {
        $purgeableAttributes = [];
        if (method_exists($this, 'getPurgeableAttributes'))
            $purgeableAttributes = $this->getPurgeableAttributes($name);

        return in_array($name, $purgeableAttributes);
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

    //
    // Magic
    //

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

    //
    // Pivot
    //

    /**
     * Create a generic pivot model instance.
     * @param \October\Rain\Database\Model $parent
     * @param array $attributes
     * @param string $table
     * @param bool $exists
     * @param string|null $using
     * @return \Igniter\Flame\Database\Pivot
     */
    public function newPivot(EloquentModel $parent, array $attributes, $table, $exists, $using = null)
    {
        return $using
            ? $using::fromRawAttributes($parent, $attributes, $table, $exists)
            : new Pivot($parent, $attributes, $table, $exists);
    }

    /**
     * Create a pivot model instance specific to a relation.
     * @param \October\Rain\Database\Model $parent
     * @param string $relationName
     * @param array $attributes
     * @param string $table
     * @param bool $exists
     * @return \October\Rain\Database\Pivot
     */
    public function newRelationPivot($relationName, $parent, $attributes, $table, $exists)
    {
        $definition = $this->getRelationDefinition($relationName);

        if (!is_null($definition) && array_key_exists('pivotModel', $definition)) {
            $pivotModel = $definition['pivotModel'];

            return new $pivotModel($parent, $attributes, $table, $exists);
        }
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
